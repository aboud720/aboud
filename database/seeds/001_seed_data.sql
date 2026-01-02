-- ═══════════════════════════════════════════════════════════════════════════
-- ABOUD STORE - Seed Data
-- ═══════════════════════════════════════════════════════════════════════════
-- Sample data for development and testing

USE `aboud_store`;

-- ═══════════════════════════════════════════════════════════════════════════
-- Admin User
-- Password: Admin@123456
-- ═══════════════════════════════════════════════════════════════════════════
INSERT INTO `users` (
    `uuid`, `username`, `email`, `email_verified_at`, `password_hash`, 
    `role`, `status`, `balance`
) VALUES (
    UUID(), 
    'admin', 
    'admin@aboud-store.com', 
    NOW(),
    '$argon2id$v=19$m=65536,t=4,p=3$dGVzdHNhbHRzYWx0c2FsdA$GkJfV7NL7FRCl1fKv9bYxvLvZpJk6qYhx9wqzp2hHhw',
    'super_admin', 
    'active',
    0
);

-- ═══════════════════════════════════════════════════════════════════════════
-- Categories
-- ═══════════════════════════════════════════════════════════════════════════
INSERT INTO `categories` (`uuid`, `name_ar`, `name_en`, `slug`, `description_ar`, `icon`, `sort_order`, `is_active`) VALUES
(UUID(), 'ألعاب الموبايل', 'Mobile Games', 'mobile-games', 'شحن ألعاب الهواتف الذكية', 'phone', 1, 1),
(UUID(), 'ألعاب الكمبيوتر', 'PC Games', 'pc-games', 'شحن ألعاب الكمبيوتر', 'pc-display', 2, 1),
(UUID(), 'ألعاب البلايستيشن', 'PlayStation', 'playstation', 'شحن ألعاب PlayStation', 'controller', 3, 1),
(UUID(), 'ألعاب Xbox', 'Xbox Games', 'xbox', 'شحن ألعاب Xbox', 'controller', 4, 1),
(UUID(), 'بطاقات متاجر', 'Store Cards', 'store-cards', 'بطاقات المتاجر الإلكترونية', 'credit-card', 5, 1),
(UUID(), 'اشتراكات', 'Subscriptions', 'subscriptions', 'اشتراكات الخدمات المختلفة', 'calendar-check', 6, 1);

-- ═══════════════════════════════════════════════════════════════════════════
-- Sample Products
-- ═══════════════════════════════════════════════════════════════════════════
INSERT INTO `products` (
    `uuid`, `category_id`, `name_ar`, `name_en`, `slug`, 
    `description_ar`, `type`, `base_price`, `cost_price`, `currency`,
    `requires_player_id`, `player_id_label`, `delivery_type`, 
    `is_featured`, `is_active`, `sort_order`
) VALUES
-- PUBG Mobile
(UUID(), 1, 'شحن PUBG Mobile UC', 'PUBG Mobile UC', 'pubg-mobile-uc',
'شحن UC لعبة ببجي موبايل - توصيل فوري', 'in_game_currency',
0, 0, 'SAR', 1, 'Player ID', 'instant', 1, 1, 1),

-- Free Fire
(UUID(), 1, 'شحن Free Fire Diamonds', 'Free Fire Diamonds', 'free-fire-diamonds',
'شحن جواهر فري فاير - توصيل فوري', 'in_game_currency',
0, 0, 'SAR', 1, 'Player ID', 'instant', 1, 1, 2),

-- Fortnite
(UUID(), 1, 'شحن Fortnite V-Bucks', 'Fortnite V-Bucks', 'fortnite-vbucks',
'شحن V-Bucks لعبة فورت نايت', 'in_game_currency',
0, 0, 'SAR', 1, 'Epic Games ID', 'instant', 1, 1, 3),

-- iTunes
(UUID(), 5, 'بطاقة iTunes', 'iTunes Gift Card', 'itunes-gift-card',
'بطاقة آيتونز للشراء من متجر أبل', 'game_card',
0, 0, 'SAR', 0, NULL, 'instant', 1, 1, 1),

-- Google Play
(UUID(), 5, 'بطاقة Google Play', 'Google Play Gift Card', 'google-play-card',
'بطاقة جوجل بلاي للشراء من المتجر', 'game_card',
0, 0, 'SAR', 0, NULL, 'instant', 1, 1, 2),

-- PlayStation Plus
(UUID(), 6, 'اشتراك PlayStation Plus', 'PlayStation Plus', 'ps-plus',
'اشتراك PlayStation Plus الشهري والسنوي', 'app_subscription',
0, 0, 'SAR', 0, NULL, 'instant', 1, 1, 1),

-- Xbox Game Pass
(UUID(), 6, 'اشتراك Xbox Game Pass', 'Xbox Game Pass', 'xbox-game-pass',
'اشتراك Xbox Game Pass Ultimate', 'app_subscription',
0, 0, 'SAR', 0, NULL, 'instant', 1, 1, 2);

-- ═══════════════════════════════════════════════════════════════════════════
-- Product Variants (PUBG Mobile)
-- ═══════════════════════════════════════════════════════════════════════════
SET @pubg_id = (SELECT id FROM products WHERE slug = 'pubg-mobile-uc' LIMIT 1);

INSERT INTO `product_variants` (
    `uuid`, `product_id`, `name_ar`, `name_en`, `value`, 
    `price`, `cost_price`, `is_active`, `sort_order`
) VALUES
(UUID(), @pubg_id, '60 UC', '60 UC', '60', 399, 350, 1, 1),
(UUID(), @pubg_id, '325 UC', '325 UC', '325', 1899, 1700, 1, 2),
(UUID(), @pubg_id, '660 UC', '660 UC', '660', 3699, 3300, 1, 3),
(UUID(), @pubg_id, '1800 UC', '1800 UC', '1800', 9299, 8500, 1, 4),
(UUID(), @pubg_id, '3850 UC', '3850 UC', '3850', 18499, 17000, 1, 5),
(UUID(), @pubg_id, '8100 UC', '8100 UC', '8100', 36999, 34000, 1, 6);

-- ═══════════════════════════════════════════════════════════════════════════
-- Product Variants (Free Fire)
-- ═══════════════════════════════════════════════════════════════════════════
SET @ff_id = (SELECT id FROM products WHERE slug = 'free-fire-diamonds' LIMIT 1);

INSERT INTO `product_variants` (
    `uuid`, `product_id`, `name_ar`, `name_en`, `value`, 
    `price`, `cost_price`, `is_active`, `sort_order`
) VALUES
(UUID(), @ff_id, '100 جوهرة', '100 Diamonds', '100', 399, 350, 1, 1),
(UUID(), @ff_id, '310 جوهرة', '310 Diamonds', '310', 1099, 950, 1, 2),
(UUID(), @ff_id, '520 جوهرة', '520 Diamonds', '520', 1899, 1700, 1, 3),
(UUID(), @ff_id, '1060 جوهرة', '1060 Diamonds', '1060', 3699, 3300, 1, 4),
(UUID(), @ff_id, '2180 جوهرة', '2180 Diamonds', '2180', 7399, 6800, 1, 5),
(UUID(), @ff_id, '5600 جوهرة', '5600 Diamonds', '5600', 18499, 17000, 1, 6);

-- ═══════════════════════════════════════════════════════════════════════════
-- Product Variants (iTunes)
-- ═══════════════════════════════════════════════════════════════════════════
SET @itunes_id = (SELECT id FROM products WHERE slug = 'itunes-gift-card' LIMIT 1);

INSERT INTO `product_variants` (
    `uuid`, `product_id`, `name_ar`, `name_en`, `value`, 
    `price`, `cost_price`, `is_active`, `sort_order`
) VALUES
(UUID(), @itunes_id, '10 دولار', '$10', '10', 4199, 3800, 1, 1),
(UUID(), @itunes_id, '25 دولار', '$25', '25', 10299, 9500, 1, 2),
(UUID(), @itunes_id, '50 دولار', '$50', '50', 20399, 19000, 1, 3),
(UUID(), @itunes_id, '100 دولار', '$100', '100', 40299, 38000, 1, 4);

-- ═══════════════════════════════════════════════════════════════════════════
-- Settings
-- ═══════════════════════════════════════════════════════════════════════════
INSERT INTO `settings` (`group`, `key`, `value`, `type`, `is_public`) VALUES
('general', 'site_name', 'ABOUD Store', 'string', 1),
('general', 'site_description', 'متجر شحن ألعاب وتطبيقات', 'string', 1),
('general', 'support_email', 'support@aboud-store.com', 'string', 1),
('general', 'support_phone', '+966500000000', 'string', 1),
('general', 'currency', 'SAR', 'string', 1),
('general', 'tax_rate', '15', 'integer', 0),
('general', 'maintenance_mode', 'false', 'boolean', 0),
('payment', 'min_deposit', '1000', 'integer', 1),
('payment', 'max_deposit', '500000', 'integer', 1);

-- ═══════════════════════════════════════════════════════════════════════════
-- Sample Coupon
-- ═══════════════════════════════════════════════════════════════════════════
INSERT INTO `coupons` (
    `code`, `type`, `value`, `min_order_amount`, `max_discount`, 
    `usage_limit`, `usage_per_user`, `is_active`, `created_by`,
    `starts_at`, `expires_at`
) VALUES
('WELCOME10', 'percentage', 10, 5000, 5000, 1000, 1, 1, 1, NOW(), DATE_ADD(NOW(), INTERVAL 1 YEAR)),
('FLAT20', 'fixed', 2000, 10000, NULL, 500, 1, 1, 1, NOW(), DATE_ADD(NOW(), INTERVAL 6 MONTH));

-- ═══════════════════════════════════════════════════════════════════════════
-- End of Seed Data
-- ═══════════════════════════════════════════════════════════════════════════
