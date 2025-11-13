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
use App\Http\Controllers\Admin\BenefitSeederController;
use App\Http\Controllers\Admin\SlideController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\ProductManagementController;
use App\Http\Controllers\Admin\StatsController;
use App\Http\Controllers\User\DeliveryPreferencesController;
use App\Http\Controllers\BrandController;

// ============================================
// RUTAS PÚBLICAS (Sin autenticación)
// ============================================

// Health check
Route::get('/health', function () {
    return response()->json(['status' => 'OK', 'timestamp' => now()]);
});

// Autenticación
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/admin/login', [AuthController::class, 'adminLogin']);
Route::post('/seller/login', [AuthController::class, 'sellerLogin']);

// Productos públicos
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{product}', [ProductController::class, 'show']);

// Marcas públicas
Route::get('/brands', [BrandController::class, 'index']);

// Orders públicos
Route::post('/orders', [OrderController::class, 'store']);
Route::get('/orders/stats', [OrderController::class, 'stats']);

// Contacto público
Route::post('/contact', [ContactController::class, 'store']);
Route::get('/contact-info', [ContactInfoController::class, 'index']);

// Consulta RENIEC pública
Route::get('/reniec/consultar/{dni}', [ReniecController::class, 'consultarDNI']);

// ============================================
// RUTAS PROTEGIDAS (Requieren autenticación)
// ============================================

Route::middleware('auth:sanctum')->group(function () {
    // ========== Autenticación y Usuario ==========
    Route::get('/user', [AuthController::class, 'user']);
    Route::put('/user/profile', [AuthController::class, 'updateProfile']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // ========== Productos (CRUD completo) ==========
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{product}', [ProductController::class, 'update']);
    Route::delete('/products/{product}', [ProductController::class, 'destroy']);

    // ========== Usuarios API ==========
    Route::apiResource('/users', UserController::class);
    Route::get('/sellers', [UserController::class, 'sellers']);
    Route::post('/users/{user}/toggle-active', [UserController::class, 'toggleActive']);

    // ========== Transacciones ==========
    Route::apiResource('/transactions', TransactionController::class);
    Route::get('/transactions/summary/financial', [TransactionController::class, 'summary']);

    // ========== Compras ==========
    Route::apiResource('/purchases', PurchaseController::class);

    // ========== Ventas ==========
    Route::apiResource('/sales', SaleController::class);
    
    // ========== Orders (rutas protegidas) ==========
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/customer', [OrderController::class, 'getCustomerOrders']); // Pedidos del cliente autenticado
    Route::get('/orders/{id}', [OrderController::class, 'show']);
    Route::put('/orders/{id}', [OrderController::class, 'update']);
    Route::post('/orders/{id}/confirm-payment', [OrderController::class, 'confirmPayment']);
    Route::delete('/orders/{id}', [OrderController::class, 'destroy']);

    // ========== Información de Contacto ==========
    Route::put('/contact-info', [ContactInfoController::class, 'update']);
    Route::get('/contact-info/history', [ContactInfoController::class, 'history']);

    // ========== Notificaciones ==========
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

    // ========== Preferencias de Entrega ==========
    Route::prefix('user')->group(function () {
        Route::get('delivery-preferences', [DeliveryPreferencesController::class, 'show']);
        Route::post('delivery-preferences', [DeliveryPreferencesController::class, 'store']);
        Route::delete('delivery-preferences', [DeliveryPreferencesController::class, 'destroy']);
    });

    // ========== Panel de Administración ==========
    Route::prefix('admin')->group(function () {
        // Verificar sesión de admin
        Route::get('me', [AuthController::class, 'adminMe']);
        Route::post('logout', [AuthController::class, 'adminLogout']);
        
        // Gestión de usuarios del admin
        Route::apiResource('users', UserManagementController::class);
        
        // Gestión de productos del admin
        Route::apiResource('products', ProductManagementController::class);
        
        // Categorías
        Route::apiResource('categories', CategoryController::class);
        
        // Marcas
        Route::get('brands', [BrandController::class, 'adminIndex']);
        Route::post('brands', [BrandController::class, 'store']);
        Route::put('brands/{id}', [BrandController::class, 'update']);
        Route::delete('brands/{id}', [BrandController::class, 'destroy']);
        
        // Beneficios
        Route::apiResource('benefits', BenefitController::class);
        Route::post('benefits/seed-defaults', [BenefitSeederController::class, 'seedDefaultBenefits']);
        
        // Slides del carrusel
        Route::apiResource('slides', SlideController::class);
        
        // Estadísticas
        Route::get('stats', [StatsController::class, 'index']);
    });
});
