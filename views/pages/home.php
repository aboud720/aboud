<?php
/**
 * Home Page
 */
$pageTitle = 'الرئيسية';
ob_start();
?>

<!-- Hero Section -->
<section class="hero">
    <div class="container">
        <div class="hero-content">
            <h1 class="hero-title">متجرك الموثوق لشحن الألعاب</h1>
            <p class="hero-subtitle">
                اشحن ألعابك المفضلة بأفضل الأسعار وبتوصيل فوري. 
                خدمة آمنة وموثوقة على مدار الساعة.
            </p>
            <div class="hero-actions">
                <a href="/products" class="btn btn-primary btn-lg">
                    <i class="bi bi-controller"></i>
                    تصفح المنتجات
                </a>
                <a href="/register" class="btn btn-outline btn-lg">
                    إنشاء حساب مجاني
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Categories Section -->
<section class="section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">الفئات</h2>
            <a href="/categories" class="section-link">
                عرض الكل <i class="bi bi-arrow-left"></i>
            </a>
        </div>
        
        <div class="grid grid-5">
            <?php foreach ($categories ?? [] as $category): ?>
                <a href="/category/<?= htmlspecialchars($category['slug']) ?>" class="card category-card">
                    <div class="category-icon">
                        <i class="bi bi-<?= htmlspecialchars($category['icon'] ?? 'controller') ?>"></i>
                    </div>
                    <h3 class="category-name"><?= htmlspecialchars($category['name_ar']) ?></h3>
                    <span class="category-count"><?= $category['products_count'] ?? 0 ?> منتج</span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Featured Products Section -->
<section class="section" style="background: var(--bg-secondary);">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">🔥 المنتجات المميزة</h2>
            <a href="/products?featured=1" class="section-link">
                عرض الكل <i class="bi bi-arrow-left"></i>
            </a>
        </div>
        
        <div class="grid grid-4" id="featured-products">
            <!-- Loaded via JavaScript -->
            <div class="text-center" style="grid-column: 1/-1;">
                <div class="spinner"></div>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="section">
    <div class="container">
        <div class="section-header" style="justify-content: center;">
            <h2 class="section-title">لماذا تختارنا؟</h2>
        </div>
        
        <div class="grid grid-4">
            <div class="card" style="text-align: center; padding: var(--spacing-xl);">
                <div style="font-size: 3rem; margin-bottom: var(--spacing-md);">⚡</div>
                <h3 style="margin-bottom: var(--spacing-sm);">توصيل فوري</h3>
                <p class="text-muted">احصل على شحنك في ثوانٍ</p>
            </div>
            
            <div class="card" style="text-align: center; padding: var(--spacing-xl);">
                <div style="font-size: 3rem; margin-bottom: var(--spacing-md);">🔒</div>
                <h3 style="margin-bottom: var(--spacing-sm);">آمن 100%</h3>
                <p class="text-muted">دفع آمن ومشفر</p>
            </div>
            
            <div class="card" style="text-align: center; padding: var(--spacing-xl);">
                <div style="font-size: 3rem; margin-bottom: var(--spacing-md);">💰</div>
                <h3 style="margin-bottom: var(--spacing-sm);">أفضل الأسعار</h3>
                <p class="text-muted">أسعار تنافسية ومخفضة</p>
            </div>
            
            <div class="card" style="text-align: center; padding: var(--spacing-xl);">
                <div style="font-size: 3rem; margin-bottom: var(--spacing-md);">🎧</div>
                <h3 style="margin-bottom: var(--spacing-sm);">دعم 24/7</h3>
                <p class="text-muted">فريق دعم متاح دائماً</p>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="section" style="background: linear-gradient(135deg, var(--primary), var(--secondary)); text-align: center;">
    <div class="container">
        <h2 style="font-size: var(--font-size-3xl); margin-bottom: var(--spacing-md);">
            جاهز للبدء؟
        </h2>
        <p style="font-size: var(--font-size-lg); margin-bottom: var(--spacing-xl); opacity: 0.9;">
            انضم لآلاف العملاء السعداء واستمتع بأفضل تجربة شحن
        </p>
        <a href="/register" class="btn btn-lg" style="background: white; color: var(--primary);">
            إنشاء حساب مجاني
        </a>
    </div>
</section>

<?php
$content = ob_get_clean();
include ROOT_PATH . '/views/layouts/main.php';
?>
