<?php

use Illuminate\Support\Facades\Route;

Route::post('/_testing/artisan/{command}', function (string $command, \Illuminate\Http\Request $request) {
    $request->validate(['parameters' => 'array']);
    \Illuminate\Support\Facades\Artisan::call($command, $request->input('parameters', []));
});
