<?php

namespace Kuldeep\CrudGenerator\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CrudGeneratorController extends Controller
{
    public function index()
    {
        return view('crud-generator::index');
    }

    public function generate(Request $request)
    {
        $module = Str::studly($request->input('module'));
        $fields = $request->input('fields', []);
        $relations = $request->input('relations', []);

        $this->generateModel($module, $fields, $relations);
        $this->generateController($module);
        $this->generateView($module, $fields);
        $this->generateMigration($module, $fields, $relations);
        $this->generatePolymorphicInverseRelations($relations);

        return redirect()->back()->with('success', 'CRUD generated successfully!');
    }

    protected function generateModel($module, $fields, $relations)
    {
        $fillable = collect($fields)->pluck('name')->filter()->map(fn($f) => "'$f'")->implode(', ');

        $relationMethods = collect($relations)->filter(
            fn($rel) =>
            !empty($rel['type']) && (!in_array($rel['type'], ['morphTo']) && !empty($rel['target']))
        )->map(function ($rel) {
            $method = !empty($rel['name']) ? Str::camel($rel['name']) : Str::camel($rel['target']);
            $target = Str::studly($rel['target']);
            $type = $rel['type'];

            $relationsMap = [
                'hasOne' => "return \$this->hasOne({$target}::class);",
                'hasMany' => "return \$this->hasMany({$target}::class);",
                'belongsTo' => "return \$this->belongsTo({$target}::class);",
                'belongsToMany' => "return \$this->belongsToMany({$target}::class);",
                'hasOneThrough' => "return \$this->hasOneThrough({$target}::class, /*IntermediateModel::class*/);",
                'hasManyThrough' => "return \$this->hasManyThrough({$target}::class, /*IntermediateModel::class*/);",
                'morphOne' => "return \$this->morphOne({$target}::class, 'imageable');",
                'morphMany' => "return \$this->morphMany({$target}::class, 'imageable');",
                'morphTo' => "return \$this->morphTo();",
                'morphToMany' => "return \$this->morphToMany({$target}::class, 'taggable');",
                'morphedByMany' => "return \$this->morphedByMany({$target}::class, 'taggable');",
            ];

            return <<<EOT
                public function {$method}()
                {
                    {$relationsMap[$type]}
                }
            EOT;
        })->implode("\n\n");

        $usesSoftDeletes = request()->has('soft_deletes') ? "use Illuminate\\Database\\Eloquent\\SoftDeletes;\n" : '';
        $softTrait = request()->has('soft_deletes') ? "    use SoftDeletes;\n" : '';

        $stub = File::get(__DIR__ . '/../../stubs/model.stub');
        $content = str_replace(
            ['{{useSoftDeletes}}', '{{softDeleteTrait}}', '{{modelName}}', '{{fillable}}', '{{relations}}'],
            [$usesSoftDeletes, $softTrait, $module, $fillable, $relationMethods],
            $stub
        );

        File::put(app_path("Models/{$module}.php"), $content);
    }

    protected function generateController($module)
    {
        $stub = File::get(__DIR__ . '/../../stubs/controller.stub');
        $content = str_replace(
            ['{{modelName}}', '{{modelVariable}}'],
            [$module, Str::camel($module)],
            $stub
        );

        File::put(app_path("Http/Controllers/{$module}Controller.php"), $content);
    }

    protected function generateView($module, $fields)
    {
        $viewDir = resource_path("views/" . Str::snake(Str::pluralStudly($module)));
        File::ensureDirectoryExists($viewDir);

        $formStub = File::get(__DIR__ . '/../../stubs/form.stub');
        $indexStub = File::get(__DIR__ . '/../../stubs/index.stub');

        $fieldHtml = collect($fields)->map(function ($field) {
            return "<div class=\"mb-4\">\n    <label class=\"block\" for=\"{$field['name']}\">{$field['name']}</label>\n    <input type=\"text\" name=\"{$field['name']}\" id=\"{$field['name']}\" class=\"form-input mt-1 block w-full\" />\n</div>";
        })->implode("\n\n");

        $formContent = str_replace(['{{modelName}}', '{{formFields}}'], [$module, $fieldHtml], $formStub);
        $indexContent = str_replace(['{{modelName}}'], [$module], $indexStub);

        File::put("{$viewDir}/create.blade.php", $formContent);
        File::put("{$viewDir}/index.blade.php", $indexContent);
    }

    protected function generateMigration($module, $fields, $relations = [])
    {
        $table = Str::snake(Str::pluralStudly($module));
        $migrationName = 'create_' . $table . '_table';
        $timestamp = now()->format('Y_m_d_His');
        $path = database_path("migrations/{$timestamp}_{$migrationName}.php");

        $schemaFields = collect($fields)->map(function ($field) {
            $fieldName = $field['name'];
            $fieldType = $field['type'] ?? 'string';
            $nullable = isset($field['nullable']) ? '->nullable()' : '';

            if ($fieldType === 'enum') {
                $values = isset($field['enum_values']) && !empty($field['enum_values'])
                    ? collect(explode(',', $field['enum_values']))->map(fn($v) => "'" . trim($v) . "'")->implode(', ')
                    : "'option1', 'option2'";
                return "\$table->enum('{$fieldName}', [{$values}]){$nullable};";
            }

            if (Str::endsWith($fieldName, '_id')) {
                return "\$table->foreignId('{$fieldName}')->constrained()->onDelete('cascade'){$nullable};";
            }

            return "\$table->{$fieldType}('{$fieldName}'){$nullable};";
        })->toArray();

        foreach ($relations as $rel) {
            if (!isset($rel['type']) || !in_array($rel['type'], ['morphOne', 'morphMany'])) {
                continue;
            }
            $schemaFields[] = "\$table->unsignedBigInteger('imageable_id');";
            $schemaFields[] = "\$table->string('imageable_type');";
            break;
        }

        if (request()->has('soft_deletes')) {
            $schemaFields[] = "\$table->softDeletes();";
        }

        $schemaFieldsStr = implode("\n            ", $schemaFields);

        $stub = File::get(__DIR__ . '/../../stubs/migration.stub');
        $content = str_replace(
            ['{{tableName}}', '{{fields}}'],
            [$table, $schemaFieldsStr],
            $stub
        );

        File::put($path, $content);
    }

    protected function generatePolymorphicInverseRelations($relations)
    {
        foreach ($relations as $rel) {
            if (!in_array($rel['type'], ['morphOne', 'morphMany']) || empty($rel['target'])) {
                continue;
            }

            $targetModel = Str::studly($rel['target']);
            $modelPath = app_path("Models/{$targetModel}.php");

            if (!File::exists($modelPath)) {
                $stub = File::get(__DIR__ . '/../../stubs/model.stub');
                $content = str_replace(
                    ['{{useSoftDeletes}}', '{{softDeleteTrait}}', '{{modelName}}', '{{fillable}}', '{{relations}}'],
                    ['', '', $targetModel, '', ''],
                    $stub
                );
                File::put($modelPath, $content);
            }

            $modelContent = File::get($modelPath);
            if (!Str::contains($modelContent, 'function imageable')) {
                $morphMethod = <<<EOT

                    public function imageable()
                    {
                        return \$this->morphTo();
                    }
                EOT;
                $modelContent = preg_replace('/}\s*$/', $morphMethod . "\n}", $modelContent);
                File::put($modelPath, $modelContent);
            }
        }
    }
}
