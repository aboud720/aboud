<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * ABOUD STORE - Route Definitions
 * ═══════════════════════════════════════════════════════════════════════════
 */

if (!defined('ABOUD_STORE')) {
    die('Direct access not allowed');
}

// Router is available via $router variable

// ═══════════════════════════════════════════════════════════════════════════
// PUBLIC ROUTES
// ═══════════════════════════════════════════════════════════════════════════

// Home
$router->get('/', 'PageController@home')->name('home');

// Static pages
$router->get('/about', 'PageController@about')->name('about');
$router->get('/contact', 'PageController@contact')->name('contact');
$router->get('/terms', 'PageController@terms')->name('terms');
$router->get('/privacy', 'PageController@privacy')->name('privacy');

// Authentication pages (web)
$router->get('/login', 'AuthController@showLogin')->name('login');
$router->post('/login', 'AuthController@login')->middleware('CsrfMiddleware');
$router->get('/register', 'AuthController@showRegister')->name('register');
$router->post('/register', 'AuthController@register')->middleware('CsrfMiddleware');
$router->get('/logout', 'AuthController@logout')->name('logout');
$router->get('/forgot-password', 'AuthController@showForgotPassword')->name('forgot-password');
$router->post('/forgot-password', 'AuthController@forgotPassword')->middleware('CsrfMiddleware');
$router->get('/reset-password/{token}', 'AuthController@showResetPassword')->name('reset-password');
$router->post('/reset-password', 'AuthController@resetPassword')->middleware('CsrfMiddleware');
$router->get('/verify-email/{token}', 'AuthController@verifyEmail')->name('verify-email');

// Products
$router->get('/products', 'ProductController@index')->name('products');
$router->get('/products/{slug}', 'ProductController@show')->name('product');
$router->get('/category/{slug}', 'ProductController@category')->name('category');
$router->get('/search', 'ProductController@search')->name('search');

// ═══════════════════════════════════════════════════════════════════════════
// AUTHENTICATED WEB ROUTES
// ═══════════════════════════════════════════════════════════════════════════

$router->group(['prefix' => '', 'middleware' => ['AuthMiddleware', 'CsrfMiddleware']], function($router) {
    
    // Profile
    $router->get('/profile', 'ProfileController@show')->name('profile');
    $router->post('/profile', 'ProfileController@update');
    $router->get('/profile/password', 'ProfileController@showChangePassword')->name('change-password');
    $router->post('/profile/password', 'ProfileController@changePassword');
    
    // Orders
    $router->get('/orders', 'OrderController@index')->name('orders');
    $router->get('/orders/{uuid}', 'OrderController@show')->name('order');
    $router->post('/orders', 'OrderController@create');
    $router->post('/orders/{uuid}/cancel', 'OrderController@cancel');
    
    // Wallet
    $router->get('/wallet', 'WalletController@index')->name('wallet');
    $router->post('/wallet/deposit', 'WalletController@deposit');
    
    // Cart
    $router->get('/cart', 'CartController@index')->name('cart');
    $router->post('/cart/add', 'CartController@add');
    $router->post('/cart/remove', 'CartController@remove');
    $router->post('/cart/update', 'CartController@update');
    $router->get('/checkout', 'CartController@checkout')->name('checkout');
    $router->post('/checkout', 'CartController@processCheckout');
    
    // Notifications
    $router->get('/notifications', 'NotificationController@index')->name('notifications');
    $router->post('/notifications/{uuid}/read', 'NotificationController@markAsRead');
    
});

// ═══════════════════════════════════════════════════════════════════════════
// ADMIN ROUTES
// ═══════════════════════════════════════════════════════════════════════════

$router->group(['prefix' => '/admin', 'middleware' => ['AuthMiddleware', 'AdminMiddleware', 'CsrfMiddleware']], function($router) {
    
    // Dashboard
    $router->get('/', 'Admin\\DashboardController@index')->name('admin.dashboard');
    
    // Products
    $router->get('/products', 'Admin\\ProductController@index')->name('admin.products');
    $router->get('/products/create', 'Admin\\ProductController@create')->name('admin.products.create');
    $router->post('/products', 'Admin\\ProductController@store');
    $router->get('/products/{id}/edit', 'Admin\\ProductController@edit')->name('admin.products.edit');
    $router->post('/products/{id}', 'Admin\\ProductController@update');
    $router->post('/products/{id}/delete', 'Admin\\ProductController@delete');
    
    // Categories
    $router->get('/categories', 'Admin\\CategoryController@index')->name('admin.categories');
    $router->post('/categories', 'Admin\\CategoryController@store');
    $router->post('/categories/{id}', 'Admin\\CategoryController@update');
    $router->post('/categories/{id}/delete', 'Admin\\CategoryController@delete');
    
    // Orders
    $router->get('/orders', 'Admin\\OrderController@index')->name('admin.orders');
    $router->get('/orders/{id}', 'Admin\\OrderController@show')->name('admin.order');
    $router->post('/orders/{id}/status', 'Admin\\OrderController@updateStatus');
    $router->post('/orders/{id}/refund', 'Admin\\OrderController@refund');
    
    // Users
    $router->get('/users', 'Admin\\UserController@index')->name('admin.users');
    $router->get('/users/{id}', 'Admin\\UserController@show')->name('admin.user');
    $router->post('/users/{id}', 'Admin\\UserController@update');
    $router->post('/users/{id}/status', 'Admin\\UserController@updateStatus');
    
    // Stock Management
    $router->get('/stock', 'Admin\\StockController@index')->name('admin.stock');
    $router->post('/stock/import', 'Admin\\StockController@import');
    
    // Coupons
    $router->get('/coupons', 'Admin\\CouponController@index')->name('admin.coupons');
    $router->post('/coupons', 'Admin\\CouponController@store');
    $router->post('/coupons/{id}', 'Admin\\CouponController@update');
    $router->post('/coupons/{id}/delete', 'Admin\\CouponController@delete');
    
    // Settings
    $router->get('/settings', 'Admin\\SettingsController@index')->name('admin.settings');
    $router->post('/settings', 'Admin\\SettingsController@update');
    
    // Reports
    $router->get('/reports', 'Admin\\ReportController@index')->name('admin.reports');
    $router->get('/reports/sales', 'Admin\\ReportController@sales');
    $router->get('/reports/products', 'Admin\\ReportController@products');
    $router->get('/reports/export', 'Admin\\ReportController@export');
    
});

// ═══════════════════════════════════════════════════════════════════════════
// API ROUTES (v1)
// ═══════════════════════════════════════════════════════════════════════════

$router->group(['prefix' => '/api/v1'], function($router) {
    
    // Public API routes
    $router->post('/auth/login', 'Api\\AuthApiController@login');
    $router->post('/auth/register', 'Api\\AuthApiController@register');
    $router->post('/auth/refresh', 'Api\\AuthApiController@refresh');
    $router->post('/auth/forgot-password', 'Api\\AuthApiController@forgotPassword');
    $router->post('/auth/reset-password', 'Api\\AuthApiController@resetPassword');
    $router->post('/auth/verify-email', 'Api\\AuthApiController@verifyEmail');
    
    // Products (public)
    $router->get('/products', 'Api\\ProductApiController@index');
    $router->get('/products/featured', 'Api\\ProductApiController@featured');
    $router->get('/products/{uuid}', 'Api\\ProductApiController@show');
    $router->get('/products/{uuid}/variants', 'Api\\ProductApiController@variants');
    
    // Categories (public)
    $router->get('/categories', 'Api\\ProductApiController@categories');
    $router->get('/categories/{slug}', 'Api\\ProductApiController@category');
    $router->get('/categories/{slug}/products', 'Api\\ProductApiController@categoryProducts');
    
    // Search (public)
    $router->get('/search', 'Api\\ProductApiController@search');
    
    // Authenticated API routes
    $router->group(['middleware' => ['ApiAuthMiddleware']], function($router) {
        
        // Auth
        $router->get('/auth/me', 'Api\\AuthApiController@me');
        $router->post('/auth/logout', 'Api\\AuthApiController@logout');
        $router->post('/auth/change-password', 'Api\\AuthApiController@changePassword');
        
        // Profile
        $router->get('/profile', 'Api\\ProfileApiController@show');
        $router->put('/profile', 'Api\\ProfileApiController@update');
        
        // Orders
        $router->get('/orders', 'Api\\OrderApiController@index');
        $router->post('/orders', 'Api\\OrderApiController@store');
        $router->get('/orders/{uuid}', 'Api\\OrderApiController@show');
        $router->post('/orders/{uuid}/cancel', 'Api\\OrderApiController@cancel');
        
        // Wallet
        $router->get('/wallet', 'Api\\WalletApiController@balance');
        $router->get('/wallet/transactions', 'Api\\WalletApiController@transactions');
        $router->get('/wallet/deposit-methods', 'Api\\WalletApiController@depositMethods');
        $router->post('/wallet/deposit', 'Api\\WalletApiController@deposit');
        
        // Notifications
        $router->get('/notifications', 'Api\\NotificationApiController@index');
        $router->post('/notifications/{uuid}/read', 'Api\\NotificationApiController@markAsRead');
        $router->post('/notifications/read-all', 'Api\\NotificationApiController@markAllAsRead');
        
    });
    
});

// ═══════════════════════════════════════════════════════════════════════════
// WEBHOOK ROUTES
// ═══════════════════════════════════════════════════════════════════════════

$router->group(['prefix' => '/webhooks'], function($router) {
    
    $router->post('/payment/stripe', 'WebhookController@stripe');
    $router->post('/payment/paypal', 'WebhookController@paypal');
    
});
