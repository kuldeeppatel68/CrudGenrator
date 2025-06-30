<?php

use Illuminate\Support\Facades\Route;
use Kuldeep\CrudGenerator\Http\Controllers\CrudGeneratorController;

Route::group(['middleware' => ['web'], 'prefix' => 'crud-generator'], function () {
    Route::get('/', [CrudGeneratorController::class, 'index'])->name('crud-generator.index');
    Route::post('/generate', [CrudGeneratorController::class, 'generate'])->name('crud-generator.generate');
});
