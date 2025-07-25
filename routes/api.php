<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Wappo\LaravelSchemaApi\Http\Controllers\SchemaApiGetController;
use Wappo\LaravelSchemaApi\Http\Controllers\SchemaApiIndexController;
use Wappo\LaravelSchemaApi\Http\Controllers\SchemaApiSyncController;

Route::middleware(config('schema-api.http.middleware'))
    ->name('schema-api.')
    ->prefix(config('schema-api.http.base_path'))
    ->group(function () {
        Route::get('/{table}/{id}', SchemaApiGetController::class)->name('show');
        Route::get('/{table}', SchemaApiIndexController::class)->name('index');
        Route::put('/sync', SchemaApiSyncController::class)->name('sync');
    });