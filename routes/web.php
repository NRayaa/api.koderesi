<?php

use App\Http\Controllers\WaybillController;
use App\Http\Controllers\WaybillFilterController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('superadmin/waybill')->group(function () {
    Route::get('delivered', [WaybillFilterController::class, 'indexDelivered']);
    Route::get('on-progress', [WaybillFilterController::class, 'indexOnProgress']);
    Route::get('return-reject', [WaybillFilterController::class, 'indexReturnReject']);
});
Route::resource('superadmin/waybill', WaybillController::class);
