<?php
/**
 * Main Layout Template
 */
if (!defined('ABOUD_STORE')) {
    die('Direct access not allowed');
}

$pageTitle = $pageTitle ?? 'ABOUD Store';
$currentUser = $user ?? null;
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="description" content="ABOUD Store - متجر شحن ألعاب وتطبيقات">
    
    <!-- CSRF Token -->
    <?= $csrf->meta() ?>
    
    <title><?= htmlspecialchars($pageTitle) ?> | ABOUD Store</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <!-- Main CSS -->
    <link rel="stylesheet" href="/css/app.css">
    
    <?php if (isset($extraCss)): ?>
        <?= $extraCss ?>
    <?php endif; ?>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <a href="/" class="navbar-brand">
                <span class="brand-icon">🎮</span>
                <span class="brand-text">ABOUD</span>
            </a>
            
            <div class="navbar-search">
                <form action="/search" method="GET" class="search-form">
                    <input 
                        type="search" 
                        name="q" 
                        class="search-input" 
                        placeholder="ابحث عن الألعاب والتطبيقات..."
                        autocomplete="off"
                    >
                    <button type="submit" class="search-btn">
                        <i class="bi bi-search"></i>
                    </button>
                </form>
            </div>
            
            <div class="navbar-menu">
                <a href="/products" class="nav-link">المنتجات</a>
                
                <?php if ($currentUser): ?>
                    <a href="/wallet" class="nav-link wallet-link">
                        <i class="bi bi-wallet2"></i>
                        <span><?= $currentUser->getFormattedBalance() ?></span>
                    </a>
                    
                    <div class="nav-dropdown">
                        <button class="nav-dropdown-toggle">
                            <span class="avatar"><?= mb_substr($currentUser->username, 0, 1) ?></span>
                            <span class="username"><?= htmlspecialchars($currentUser->username) ?></span>
                            <i class="bi bi-chevron-down"></i>
                        </button>
                        <div class="nav-dropdown-menu">
                            <a href="/profile" class="dropdown-item">
                                <i class="bi bi-person"></i> الملف الشخصي
                            </a>
                            <a href="/orders" class="dropdown-item">
                                <i class="bi bi-bag"></i> طلباتي
                            </a>
                            <a href="/wallet" class="dropdown-item">
                                <i class="bi bi-wallet2"></i> المحفظة
                            </a>
                            <a href="/notifications" class="dropdown-item">
                                <i class="bi bi-bell"></i> الإشعارات
                            </a>
                            <?php if ($currentUser->isAdmin()): ?>
                                <hr class="dropdown-divider">
                                <a href="/admin" class="dropdown-item">
                                    <i class="bi bi-gear"></i> لوحة التحكم
                                </a>
                            <?php endif; ?>
                            <hr class="dropdown-divider">
                            <a href="/logout" class="dropdown-item text-danger">
                                <i class="bi bi-box-arrow-right"></i> تسجيل الخروج
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="/login" class="nav-link">تسجيل الدخول</a>
                    <a href="/register" class="btn btn-primary btn-sm">إنشاء حساب</a>
                <?php endif; ?>
            </div>
            
            <button class="navbar-toggle" id="navbarToggle">
                <i class="bi bi-list"></i>
            </button>
        </div>
    </nav>
    
    <!-- Flash Messages -->
    <?php if ($flash = $app->session()->getFlash('success')): ?>
        <div class="alert alert-success">
            <i class="bi bi-check-circle"></i>
            <?= htmlspecialchars($flash) ?>
        </div>
    <?php endif; ?>
    
    <?php if ($flash = $app->session()->getFlash('error')): ?>
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-circle"></i>
            <?= htmlspecialchars($flash) ?>
        </div>
    <?php endif; ?>
    
    <!-- Main Content -->
    <main class="main-content">
        <?= $content ?? '' ?>
    </main>
    
    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-section">
                    <h4 class="footer-title">ABOUD Store</h4>
                    <p class="footer-text">
                        متجرك الموثوق لشحن الألعاب والتطبيقات. 
                        خدمة سريعة وآمنة على مدار الساعة.
                    </p>
                    <div class="social-links">
                        <a href="#" class="social-link"><i class="bi bi-twitter-x"></i></a>
                        <a href="#" class="social-link"><i class="bi bi-instagram"></i></a>
                        <a href="#" class="social-link"><i class="bi bi-discord"></i></a>
                        <a href="#" class="social-link"><i class="bi bi-telegram"></i></a>
                    </div>
                </div>
                
                <div class="footer-section">
                    <h4 class="footer-title">روابط سريعة</h4>
                    <ul class="footer-links">
                        <li><a href="/products">جميع المنتجات</a></li>
                        <li><a href="/about">من نحن</a></li>
                        <li><a href="/contact">تواصل معنا</a></li>
                        <li><a href="/faq">الأسئلة الشائعة</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4 class="footer-title">الدعم</h4>
                    <ul class="footer-links">
                        <li><a href="/help">مركز المساعدة</a></li>
                        <li><a href="/terms">الشروط والأحكام</a></li>
                        <li><a href="/privacy">سياسة الخصوصية</a></li>
                        <li><a href="/refund">سياسة الاسترداد</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4 class="footer-title">طرق الدفع</h4>
                    <div class="payment-methods">
                        <img src="/images/payment/visa.svg" alt="Visa" class="payment-icon">
                        <img src="/images/payment/mastercard.svg" alt="Mastercard" class="payment-icon">
                        <img src="/images/payment/mada.svg" alt="Mada" class="payment-icon">
                        <img src="/images/payment/apple-pay.svg" alt="Apple Pay" class="payment-icon">
                        <img src="/images/payment/stc-pay.svg" alt="STC Pay" class="payment-icon">
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> ABOUD Store. جميع الحقوق محفوظة.</p>
            </div>
        </div>
    </footer>
    
    <!-- Scripts -->
    <script src="/js/app.js"></script>
    
    <?php if (isset($extraJs)): ?>
        <?= $extraJs ?>
    <?php endif; ?>
</body>
</html>
