<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ContactInfoController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ReniecController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\BenefitController;
use App\Http\Controllers\Admin\SlideController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\ProductManagementController;
use App\Http\Controllers\Admin\StatsController;
use App\Http\Controllers\User\DeliveryPreferencesController;

Route::get('/health', function () {
    return response()->json(['status' => 'OK', 'timestamp' => now()]);
});

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::post('/admin/login', [AuthController::class, 'adminLogin']);
Route::post('/admin/logout', [AuthController::class, 'adminLogout']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);
});

Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{product}', [ProductController::class, 'show']);

// Orders - Public routes
Route::post('/orders', [OrderController::class, 'store']);
Route::get('/orders/stats', [OrderController::class, 'stats']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{product}', [ProductController::class, 'update']);
    Route::delete('/products/{product}', [ProductController::class, 'destroy']);

    Route::apiResource('/users', UserController::class);
    Route::get('/sellers', [UserController::class, 'sellers']);
    Route::post('/users/{user}/toggle-active', [UserController::class, 'toggleActive']);

    Route::apiResource('/transactions', TransactionController::class);
    Route::get('/transactions/summary/financial', [TransactionController::class, 'summary']);

    Route::apiResource('/purchases', PurchaseController::class);

    Route::apiResource('/sales', SaleController::class);
    
    // Orders - Protected routes
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
    Route::put('/orders/{id}', [OrderController::class, 'update']);
    Route::post('/orders/{id}/confirm-payment', [OrderController::class, 'confirmPayment']);
    Route::delete('/orders/{id}', [OrderController::class, 'destroy']);
});

Route::post('/contact', [ContactController::class, 'store']);

Route::get('/contact-info', [ContactInfoController::class, 'index']);
Route::middleware('auth:sanctum')->group(function () {
    Route::put('/contact-info', [ContactInfoController::class, 'update']);
    Route::get('/contact-info/history', [ContactInfoController::class, 'history']);
});

// Ruta pública para consultar DNI en RENIEC
Route::get('/reniec/consultar/{dni}', [ReniecController::class, 'consultarDNI']);

// Rutas de Notificaciones (protegidas con auth:sanctum)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/stats', [NotificationController::class, 'stats']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('/notifications', [NotificationController::class, 'store']);
    Route::get('/notifications/{id}', [NotificationController::class, 'show']);
    Route::put('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::put('/notifications/{id}/unread', [NotificationController::class, 'markAsUnread']);
    Route::put('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);
    Route::delete('/notifications/clear/read', [NotificationController::class, 'clearRead']);
    Route::delete('/notifications/clear/all', [NotificationController::class, 'clearAll']);
});

// Rutas de Admin (protegidas con auth:sanctum)
Route::middleware('auth:sanctum')->prefix('admin')->group(function () {
    Route::apiResource('users', UserManagementController::class);
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('benefits', BenefitController::class);
    Route::apiResource('slides', SlideController::class);
    Route::get('stats', [StatsController::class, 'index']);
});

// Rutas de preferencias de usuario
Route::middleware('auth:sanctum')->prefix('user')->group(function () {
    Route::get('delivery-preferences', [DeliveryPreferencesController::class, 'show']);
    Route::post('delivery-preferences', [DeliveryPreferencesController::class, 'store']);
    Route::delete('delivery-preferences', [DeliveryPreferencesController::class, 'destroy']);
});
