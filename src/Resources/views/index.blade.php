@extends('crud-generator::layouts.app')

@section('content')
    <div class="max-w-4xl mx-auto mt-10 bg-white p-6 rounded-xl shadow-md">
        <h2 class="text-2xl font-bold mb-4">Laravel CRUD Generator</h2>

        @if (session('success'))
            <div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        <form method="POST" action="{{ route('crud-generator.generate') }}">
            @csrf

            <!-- Module Name -->
            <div class="mb-4">
                <label class="block font-semibold">Module Name</label>
                <input type="text" name="module_name" required class="w-full border rounded px-3 py-2 mt-1"
                    placeholder="e.g. Post">
            </div>
            <!-- Soft Deletes -->
            <div class="mb-4">
                <label class="flex items-center space-x-2">
                    <input type="checkbox" name="soft_deletes">
                    <span>Enable Soft Deletes</span>
                </label>
            </div>
            <!-- Fields -->
            <div class="mb-4">
                <label class="block font-semibold">Fields</label>
                <div id="fields">
                    <div class="flex space-x-2 mb-2">
                        <input type="text" name="fields[0][name]" placeholder="Field Name"
                            class="w-1/2 border px-2 py-1 rounded">
                        <select name="fields[0][type]" onchange="toggleEnumOptions(this, 0)"
                            class="w-1/4 border px-2 py-1 rounded">
                            <optgroup label="String Types">
                                <option value="string">string</option>
                                <option value="char">char</option>
                                <option value="text">text</option>
                                <option value="mediumText">mediumText</option>
                                <option value="longText">longText</option>
                                <option value="enum">enum</option>
                            </optgroup>
                            <optgroup label="Numeric Types">
                                <option value="integer">integer</option>
                                <option value="bigInteger">bigInteger</option>
                                <option value="mediumInteger">mediumInteger</option>
                                <option value="smallInteger">smallInteger</option>
                                <option value="tinyInteger">tinyInteger</option>
                                <option value="float">float</option>
                                <option value="double">double</option>
                                <option value="decimal">decimal</option>
                            </optgroup>
                            <optgroup label="Boolean">
                                <option value="boolean">boolean</option>
                            </optgroup>
                            <optgroup label="Date/Time">
                                <option value="date">date</option>
                                <option value="datetime">datetime</option>
                                <option value="time">time</option>
                                <option value="timestamp">timestamp</option>
                                <option value="year">year</option>
                            </optgroup>
                            <optgroup label="JSON & Binary">
                                <option value="json">json</option>
                                <option value="binary">binary</option>
                                <option value="uuid">uuid</option>
                                <option value="ipAddress">ipAddress</option>
                                <option value="macAddress">macAddress</option>
                            </optgroup>
                        </select>

                        <input type="text" name="fields[0][enum_values]" placeholder="Enum Values (comma-separated)"
                            class="w-1/4 border px-2 py-1 rounded enum-field hidden">

                        <label class="flex items-center text-sm space-x-1">
                            <input type="checkbox" name="fields[0][nullable]"> <span>Nullable</span>
                        </label>
                    </div>
                </div>
                <button type="button" id="add-field" class="bg-gray-100 px-3 py-1 text-sm mt-2 rounded">+ Add
                    Field</button>
            </div>

            <!-- Relations -->
            <div class="mb-4">
                <label class="block font-semibold">Relationships</label>
                <div id="relations">
                    <div class="flex space-x-2 mb-2">
                        <select name="relations[0][type]" class="w-1/2 border px-2 py-1 rounded">
                            <option value="hasOne">hasOne</option>
                            <option value="belongsTo">belongsTo</option>
                            <option value="hasMany">hasMany</option>
                            <option value="belongsToMany">belongsToMany</option>
                            <option value="hasOneThrough">hasOneThrough</option>
                            <option value="hasManyThrough">hasManyThrough</option>
                            <option value="morphOne">morphOne</option>
                            <option value="morphMany">morphMany</option>
                            <option value="morphTo">morphTo</option>
                            <option value="morphToMany">morphToMany</option>
                            <option value="morphedByMany">morphedByMany</option>
                        </select>
                        <input type="text" name="relations[0][target]" placeholder="Target Model (e.g. User)"
                            class="w-1/2 border px-2 py-1 rounded">
                        <input type="text" name="relations[0][name]" placeholder="Method Name (optional)"
                            class="w-1/3 border px-2 py-1 rounded">
                    </div>
                </div>
                <button type="button" id="add-relation" class="bg-gray-100 px-3 py-1 text-sm mt-2 rounded">+ Add
                    Relation</button>
            </div>

            <!-- Submit -->
            <div class="mt-6">
                <button class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">Generate CRUD</button>
            </div>
        </form>
    </div>

    <script>
        let fieldIndex = 1;
        let relationIndex = 1;

        document.getElementById('add-field').addEventListener('click', function() {
            const html = `
                <div class="flex mb-2 space-x-2 items-center">
                    <input type="text" name="fields[${fieldIndex}][name]" placeholder="Field Name" class="w-1/4 border px-2 py-1 rounded">

                    <select name="fields[${fieldIndex}][type]" onchange="toggleEnumOptions(this, ${fieldIndex})" class="w-1/4 border px-2 py-1 rounded">
                        <optgroup label="String Types">
                            <option value="string">string</option>
                            <option value="char">char</option>
                            <option value="text">text</option>
                            <option value="mediumText">mediumText</option>
                            <option value="longText">longText</option>
                            <option value="enum">enum</option>
                        </optgroup>
                        <optgroup label="Numeric Types">
                            <option value="integer">integer</option>
                            <option value="bigInteger">bigInteger</option>
                            <option value="mediumInteger">mediumInteger</option>
                            <option value="smallInteger">smallInteger</option>
                            <option value="tinyInteger">tinyInteger</option>
                            <option value="float">float</option>
                            <option value="double">double</option>
                            <option value="decimal">decimal</option>
                        </optgroup>
                        <optgroup label="Boolean">
                            <option value="boolean">boolean</option>
                        </optgroup>
                        <optgroup label="Date/Time">
                            <option value="date">date</option>
                            <option value="datetime">datetime</option>
                            <option value="time">time</option>
                            <option value="timestamp">timestamp</option>
                            <option value="year">year</option>
                        </optgroup>
                        <optgroup label="JSON & Binary">
                            <option value="json">json</option>
                            <option value="binary">binary</option>
                            <option value="uuid">uuid</option>
                            <option value="ipAddress">ipAddress</option>
                            <option value="macAddress">macAddress</option>
                        </optgroup>
                    </select>

                    <input type="text" name="fields[${fieldIndex}][enum_values]" placeholder="Enum Values (comma-separated)" class="w-1/4 border px-2 py-1 rounded enum-field hidden">

                    <label class="flex items-center text-sm space-x-1">
                        <input type="checkbox" name="fields[${fieldIndex}][nullable]"> <span>Nullable</span>
                    </label>
                </div>`;
            document.getElementById('fields').insertAdjacentHTML('beforeend', html);
            fieldIndex++;
        });

        function toggleEnumOptions(select, index) {
            const parent = select.closest('.flex');
            const enumField = parent.querySelector('.enum-field');
            if (select.value === 'enum') {
                enumField.classList.remove('hidden');
            } else {
                enumField.classList.add('hidden');
            }
        }

        document.getElementById('add-relation').addEventListener('click', function() {
            const html = `
        <div class="flex space-x-2 mb-2">
            <select name="relations[${relationIndex}][type]" class="w-1/2 border px-2 py-1 rounded">
                <option value="">-- Select Relation --</option>
                <option value="hasOne">hasOne</option>
                <option value="belongsTo">belongsTo</option>
                <option value="hasMany">hasMany</option>
                <option value="belongsToMany">belongsToMany</option>
                <option value="hasOneThrough">hasOneThrough</option>
                <option value="hasManyThrough">hasManyThrough</option>
                <option value="morphOne">morphOne</option>
                <option value="morphMany">morphMany</option>
                <option value="morphTo">morphTo</option>
                <option value="morphToMany">morphToMany</option>
                <option value="morphedByMany">morphedByMany</option>
            </select>
            <input type="text" name="relations[${relationIndex}][target]" placeholder="Target Model (e.g. User)" class="w-1/2 border px-2 py-1 rounded">
            <input type="text" name="relations[${relationIndex}][name]" placeholder="Method Name (optional)" class="w-1/3 border px-2 py-1 rounded">
        </div>`;
            document.getElementById('relations').insertAdjacentHTML('beforeend', html);
            relationIndex++;
        });
    </script>
@endsection
