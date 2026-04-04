<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\MasterController;
use App\Http\Controllers\Api\PurchaseInvoiceController;
use App\Http\Controllers\Api\VisitController;
use App\Http\Controllers\Api\BusinessController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\SiteController;
use App\Http\Controllers\Api\SiteVisitController;
use App\Http\Controllers\Api\DispatchController;




Route::get('/ping', function () {
    return response()->json(['pong' => true]);
});

Route::get('/check-api', function () {
    return response()->json([
        'status' => true,
        'message' => 'api.php route is working'
    ]);
});

Route::post('/login', [AuthController::class, 'login']);
Route::post('/refresh-token', [AuthController::class, 'refresh']);



Route::post('/register-user', [AuthController::class, 'registerUser']);


Route::get('/test', function () {
    return response()->json([
        'status' => true,
        'message' => 'API is working fine',
        'app' => config('app.name'),
        'time' => now()->toDateTimeString()
    ]);
});




Route::middleware('admin')->group(function () {
    Route::post('/category/save', [CategoryController::class, 'saveCategory']);
    Route::post('/product/save', [ProductController::class, 'saveProduct']);

     Route::delete('/product/delete', [ProductController::class, 'deleteProduct']);
     Route::delete('/category/delete', [CategoryController::class, 'deleteCategory']);


    Route::get('/users', [AuthController::class, 'userList']);


    Route::post('/purchase-invoice/save', [PurchaseInvoiceController::class, 'store']);
    Route::get('/purchase-invoice', [PurchaseInvoiceController::class, 'show']);
    Route::delete('/purchase-invoice', [PurchaseInvoiceController::class, 'destroy']);
    Route::get('/purchase-invoice/list', [PurchaseInvoiceController::class, 'list']);


});

//sales side

Route::middleware(['auth:api', 'check.active', 'sales'])->group(function () {

    Route::post('/visit/save', [VisitController::class, 'saveVisit']);
    Route::get('/visits', [VisitController::class, 'getAllVisit']);
    Route::delete('/visit/delete', [VisitController::class, 'deleteVisit']);

    Route::post('/business/save', [BusinessController::class, 'saveBusiness']);
    Route::get('/businesses', [BusinessController::class, 'getBusinesses']);
    Route::delete('/business/delete', [BusinessController::class, 'deleteBusiness']);

    Route::get('/visits/by-business', [VisitController::class, 'getVisitByBusiness']);

    Route::get('/products/by-category', [ProductController::class, 'getProductsByCategory']);

    //order
    Route::post('/order/save', [OrderController::class, 'store']);
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/by-visit', [OrderController::class, 'getByVisit']);
    Route::delete('/order/delete', [OrderController::class, 'destroy']);
    Route::get('/order/by-id', [OrderController::class, 'getById']);


    // Sites API
    Route::post('/site/save', [SiteController::class, 'save']);
    Route::get('/sites', [SiteController::class, 'index']);
    Route::get('/site/by-id', [SiteController::class, 'getById']);
    Route::delete('/site/delete', [SiteController::class, 'delete']);

    //site visit

    Route::post('/site-visit/save', [SiteVisitController::class, 'save']);
    Route::get('/site-visits', [SiteVisitController::class, 'index']);
    Route::get('/site-visits/by-site', [SiteVisitController::class, 'getBySite']);
    Route::delete('/site-visit/delete', [SiteVisitController::class, 'delete']);



});



//login user all

Route::middleware(['auth:api', 'check.active', 'auth.only'])->group(function () {

    Route::get('/categories', [CategoryController::class, 'getCategories']);
    Route::get('/products', [ProductController::class, 'getProducts']);
    Route::get('/get-master', [MasterController::class, 'getMaster']);
});



//dispatcher

Route::middleware(['check.active', 'dispatcher'])->group(function () {

    Route::post('/dispatch/create', [DispatchController::class, 'store']);
     Route::get('/dispatch/list', [DispatchController::class, 'list']);

});

Route::middleware(['auth:api','check.active','role:admin,sales,dispatcher'])->group(function () {

    Route::get('/dispatch/list', [DispatchController::class, 'list']);

});
