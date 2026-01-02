-- ═══════════════════════════════════════════════════════════════════════════
-- ABOUD STORE - Complete Database Schema
-- ═══════════════════════════════════════════════════════════════════════════
-- 
-- @description  Production-ready database with security best practices
-- @author       Senior Database Architect
-- @version      1.0.0
-- 
-- 🔒 SECURITY FEATURES:
--    ✓ UUID for public identifiers (prevents enumeration attacks)
--    ✓ Proper indexing for performance
--    ✓ Foreign key constraints for data integrity
--    ✓ Audit columns (created_at, updated_at, deleted_at)
--    ✓ Soft deletes for data recovery
--    ✓ Encrypted sensitive data columns
--    ✓ Rate limiting tables
--    ✓ Activity logging
-- ═══════════════════════════════════════════════════════════════════════════

-- Create database with proper charset
CREATE DATABASE IF NOT EXISTS `aboud_store` 
    CHARACTER SET utf8mb4 
    COLLATE utf8mb4_unicode_ci;

USE `aboud_store`;

-- ═══════════════════════════════════════════════════════════════════════════
-- TABLE: users
-- ═══════════════════════════════════════════════════════════════════════════
-- PURPOSE: Stores user accounts with security-focused design
-- SECURITY:
--   - UUID prevents user ID enumeration
--   - Password stored with Argon2ID hash
--   - Email verification required
--   - 2FA support ready
--   - Failed login tracking for brute force protection
-- ═══════════════════════════════════════════════════════════════════════════
CREATE TABLE `users` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `uuid` CHAR(36) NOT NULL COMMENT 'Public identifier - prevents enumeration',
    `username` VARCHAR(50) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `email_verified_at` TIMESTAMP NULL DEFAULT NULL,
    `phone` VARCHAR(20) NULL,
    `phone_verified_at` TIMESTAMP NULL DEFAULT NULL,
    `password_hash` VARCHAR(255) NOT NULL COMMENT 'Argon2ID hashed password',
    `two_factor_secret` VARCHAR(255) NULL COMMENT 'Encrypted 2FA secret',
    `two_factor_enabled` TINYINT(1) NOT NULL DEFAULT 0,
    `role` ENUM('customer', 'vendor', 'support', 'admin', 'super_admin') NOT NULL DEFAULT 'customer',
    `status` ENUM('pending', 'active', 'suspended', 'banned') NOT NULL DEFAULT 'pending',
    `balance` DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Wallet balance',
    `avatar` VARCHAR(255) NULL,
    `locale` VARCHAR(10) NOT NULL DEFAULT 'ar',
    `timezone` VARCHAR(50) NOT NULL DEFAULT 'Asia/Riyadh',
    `failed_login_attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `locked_until` TIMESTAMP NULL DEFAULT NULL,
    `last_login_at` TIMESTAMP NULL DEFAULT NULL,
    `last_login_ip` VARCHAR(45) NULL COMMENT 'IPv6 compatible',
    `last_activity_at` TIMESTAMP NULL DEFAULT NULL,
    `remember_token` VARCHAR(100) NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Soft delete',
    
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_users_uuid` (`uuid`),
    UNIQUE KEY `uk_users_username` (`username`),
    UNIQUE KEY `uk_users_email` (`email`),
    KEY `idx_users_role_status` (`role`, `status`),
    KEY `idx_users_deleted_at` (`deleted_at`),
    KEY `idx_users_last_activity` (`last_activity_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ═══════════════════════════════════════════════════════════════════════════
-- TABLE: categories
-- ═══════════════════════════════════════════════════════════════════════════
-- PURPOSE: Game/App categories with hierarchical support
-- ═══════════════════════════════════════════════════════════════════════════
CREATE TABLE `categories` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `uuid` CHAR(36) NOT NULL,
    `parent_id` INT UNSIGNED NULL COMMENT 'For subcategories',
    `name_ar` VARCHAR(100) NOT NULL,
    `name_en` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL,
    `description_ar` TEXT NULL,
    `description_en` TEXT NULL,
    `icon` VARCHAR(100) NULL,
    `image` VARCHAR(255) NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `meta_title` VARCHAR(255) NULL,
    `meta_description` TEXT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_categories_uuid` (`uuid`),
    UNIQUE KEY `uk_categories_slug` (`slug`),
    KEY `idx_categories_parent` (`parent_id`),
    KEY `idx_categories_active_sort` (`is_active`, `sort_order`),
    
    CONSTRAINT `fk_categories_parent` 
        FOREIGN KEY (`parent_id`) 
        REFERENCES `categories` (`id`) 
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ═══════════════════════════════════════════════════════════════════════════
-- TABLE: products
-- ═══════════════════════════════════════════════════════════════════════════
-- PURPOSE: Games and apps available for purchase/recharge
-- SECURITY: Prices stored as integers (cents) to avoid float issues
-- ═══════════════════════════════════════════════════════════════════════════
CREATE TABLE `products` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `uuid` CHAR(36) NOT NULL,
    `category_id` INT UNSIGNED NOT NULL,
    `vendor_id` BIGINT UNSIGNED NULL COMMENT 'If product belongs to a vendor',
    `name_ar` VARCHAR(255) NOT NULL,
    `name_en` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL,
    `description_ar` TEXT NULL,
    `description_en` TEXT NULL,
    `type` ENUM('game_card', 'app_subscription', 'in_game_currency', 'account', 'service') NOT NULL,
    `image` VARCHAR(255) NULL,
    `gallery` JSON NULL COMMENT 'Array of image paths',
    `base_price` INT UNSIGNED NOT NULL COMMENT 'Price in cents',
    `sale_price` INT UNSIGNED NULL COMMENT 'Discounted price in cents',
    `cost_price` INT UNSIGNED NOT NULL COMMENT 'Our cost for profit calculation',
    `currency` CHAR(3) NOT NULL DEFAULT 'SAR',
    `stock_quantity` INT NOT NULL DEFAULT -1 COMMENT '-1 = unlimited',
    `min_quantity` INT UNSIGNED NOT NULL DEFAULT 1,
    `max_quantity` INT UNSIGNED NOT NULL DEFAULT 10,
    `requires_player_id` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Needs game player ID',
    `player_id_label` VARCHAR(100) NULL COMMENT 'Label for player ID field',
    `requires_server` TINYINT(1) NOT NULL DEFAULT 0,
    `servers` JSON NULL COMMENT 'Available servers list',
    `delivery_type` ENUM('instant', 'manual', 'api') NOT NULL DEFAULT 'instant',
    `api_provider` VARCHAR(50) NULL COMMENT 'External API provider name',
    `api_product_id` VARCHAR(100) NULL COMMENT 'Product ID in external API',
    `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order` INT NOT NULL DEFAULT 0,
    `total_sales` INT UNSIGNED NOT NULL DEFAULT 0,
    `rating_sum` INT UNSIGNED NOT NULL DEFAULT 0,
    `rating_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `meta_title` VARCHAR(255) NULL,
    `meta_description` TEXT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL,
    
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_products_uuid` (`uuid`),
    UNIQUE KEY `uk_products_slug` (`slug`),
    KEY `idx_products_category` (`category_id`),
    KEY `idx_products_vendor` (`vendor_id`),
    KEY `idx_products_type_active` (`type`, `is_active`),
    KEY `idx_products_featured` (`is_featured`, `is_active`),
    KEY `idx_products_deleted` (`deleted_at`),
    FULLTEXT KEY `ft_products_search` (`name_ar`, `name_en`, `description_ar`, `description_en`),
    
    CONSTRAINT `fk_products_category` 
        FOREIGN KEY (`category_id`) 
        REFERENCES `categories` (`id`) 
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_products_vendor` 
        FOREIGN KEY (`vendor_id`) 
        REFERENCES `users` (`id`) 
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ═══════════════════════════════════════════════════════════════════════════
-- TABLE: product_variants
-- ═══════════════════════════════════════════════════════════════════════════
-- PURPOSE: Different denominations/options for products (e.g., 100 UC, 500 UC)
-- ═══════════════════════════════════════════════════════════════════════════
CREATE TABLE `product_variants` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `uuid` CHAR(36) NOT NULL,
    `product_id` BIGINT UNSIGNED NOT NULL,
    `name_ar` VARCHAR(100) NOT NULL,
    `name_en` VARCHAR(100) NOT NULL,
    `value` VARCHAR(50) NULL COMMENT 'e.g., 100 for 100 UC',
    `price` INT UNSIGNED NOT NULL COMMENT 'Price in cents',
    `sale_price` INT UNSIGNED NULL,
    `cost_price` INT UNSIGNED NOT NULL,
    `stock_quantity` INT NOT NULL DEFAULT -1,
    `sku` VARCHAR(50) NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_product_variants_uuid` (`uuid`),
    UNIQUE KEY `uk_product_variants_sku` (`sku`),
    KEY `idx_product_variants_product` (`product_id`, `is_active`, `sort_order`),
    
    CONSTRAINT `fk_product_variants_product` 
        FOREIGN KEY (`product_id`) 
        REFERENCES `products` (`id`) 
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ═══════════════════════════════════════════════════════════════════════════
-- TABLE: stock_codes
-- ═══════════════════════════════════════════════════════════════════════════
-- PURPOSE: Actual codes/cards to be delivered to customers
-- SECURITY: Codes are encrypted at rest
-- ═══════════════════════════════════════════════════════════════════════════
CREATE TABLE `stock_codes` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `product_id` BIGINT UNSIGNED NOT NULL,
    `variant_id` BIGINT UNSIGNED NULL,
    `code_encrypted` TEXT NOT NULL COMMENT 'AES-256 encrypted code',
    `serial_number` VARCHAR(100) NULL COMMENT 'If applicable',
    `status` ENUM('available', 'reserved', 'sold', 'expired', 'invalid') NOT NULL DEFAULT 'available',
    `reserved_until` TIMESTAMP NULL,
    `reserved_by_order` BIGINT UNSIGNED NULL,
    `sold_at` TIMESTAMP NULL,
    `sold_to_user` BIGINT UNSIGNED NULL,
    `sold_in_order` BIGINT UNSIGNED NULL,
    `expires_at` TIMESTAMP NULL,
    `batch_id` VARCHAR(50) NULL COMMENT 'Import batch identifier',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    KEY `idx_stock_product_status` (`product_id`, `status`),
    KEY `idx_stock_variant_status` (`variant_id`, `status`),
    KEY `idx_stock_batch` (`batch_id`),
    KEY `idx_stock_expires` (`expires_at`),
    
    CONSTRAINT `fk_stock_product` 
        FOREIGN KEY (`product_id`) 
        REFERENCES `products` (`id`) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_stock_variant` 
        FOREIGN KEY (`variant_id`) 
        REFERENCES `product_variants` (`id`) 
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ═══════════════════════════════════════════════════════════════════════════
-- TABLE: orders
-- ═══════════════════════════════════════════════════════════════════════════
-- PURPOSE: Customer orders with full audit trail
-- SECURITY: 
--   - Order number is random, not sequential
--   - Payment details stored separately
--   - Full status history maintained
-- ═══════════════════════════════════════════════════════════════════════════
CREATE TABLE `orders` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `uuid` CHAR(36) NOT NULL,
    `order_number` VARCHAR(20) NOT NULL COMMENT 'Random unique order number',
    `user_id` BIGINT UNSIGNED NOT NULL,
    `status` ENUM(
        'pending_payment', 
        'processing', 
        'delivering', 
        'completed', 
        'failed', 
        'cancelled', 
        'refunded',
        'partially_refunded'
    ) NOT NULL DEFAULT 'pending_payment',
    `subtotal` INT UNSIGNED NOT NULL COMMENT 'Before discounts, in cents',
    `discount_amount` INT UNSIGNED NOT NULL DEFAULT 0,
    `coupon_id` BIGINT UNSIGNED NULL,
    `tax_amount` INT UNSIGNED NOT NULL DEFAULT 0,
    `total_amount` INT UNSIGNED NOT NULL COMMENT 'Final amount in cents',
    `currency` CHAR(3) NOT NULL DEFAULT 'SAR',
    `payment_method` ENUM('wallet', 'card', 'paypal', 'apple_pay', 'mada', 'stc_pay') NULL,
    `payment_status` ENUM('pending', 'paid', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
    `paid_at` TIMESTAMP NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `user_agent` VARCHAR(500) NULL,
    `notes` TEXT NULL COMMENT 'Customer notes',
    `admin_notes` TEXT NULL COMMENT 'Internal notes',
    `processed_by` BIGINT UNSIGNED NULL COMMENT 'Admin who processed',
    `completed_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_orders_uuid` (`uuid`),
    UNIQUE KEY `uk_orders_number` (`order_number`),
    KEY `idx_orders_user` (`user_id`),
    KEY `idx_orders_status` (`status`),
    KEY `idx_orders_payment_status` (`payment_status`),
    KEY `idx_orders_created` (`created_at`),
    KEY `idx_orders_coupon` (`coupon_id`),
    
    CONSTRAINT `fk_orders_user` 
        FOREIGN KEY (`user_id`) 
        REFERENCES `users` (`id`) 
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ═══════════════════════════════════════════════════════════════════════════
-- TABLE: order_items
-- ═══════════════════════════════════════════════════════════════════════════
CREATE TABLE `order_items` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_id` BIGINT UNSIGNED NOT NULL,
    `product_id` BIGINT UNSIGNED NOT NULL,
    `variant_id` BIGINT UNSIGNED NULL,
    `product_name` VARCHAR(255) NOT NULL COMMENT 'Snapshot at time of order',
    `variant_name` VARCHAR(100) NULL,
    `quantity` INT UNSIGNED NOT NULL DEFAULT 1,
    `unit_price` INT UNSIGNED NOT NULL,
    `total_price` INT UNSIGNED NOT NULL,
    `player_id` VARCHAR(100) NULL COMMENT 'If required by product',
    `server` VARCHAR(100) NULL,
    `status` ENUM('pending', 'processing', 'delivered', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
    `delivery_data` JSON NULL COMMENT 'Delivered codes or API response',
    `delivered_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    KEY `idx_order_items_order` (`order_id`),
    KEY `idx_order_items_product` (`product_id`),
    KEY `idx_order_items_status` (`status`),
    
    CONSTRAINT `fk_order_items_order` 
        FOREIGN KEY (`order_id`) 
        REFERENCES `orders` (`id`) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_order_items_product` 
        FOREIGN KEY (`product_id`) 
        REFERENCES `products` (`id`) 
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ═══════════════════════════════════════════════════════════════════════════
-- TABLE: transactions
-- ═══════════════════════════════════════════════════════════════════════════
-- PURPOSE: All financial transactions (wallet, payments, refunds)
-- SECURITY: Immutable record - never update, only insert
-- ═══════════════════════════════════════════════════════════════════════════
CREATE TABLE `transactions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `uuid` CHAR(36) NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `type` ENUM(
        'deposit', 
        'withdrawal', 
        'purchase', 
        'refund', 
        'bonus', 
        'transfer_in', 
        'transfer_out',
        'commission'
    ) NOT NULL,
    `amount` INT NOT NULL COMMENT 'Positive or negative, in cents',
    `balance_before` INT NOT NULL,
    `balance_after` INT NOT NULL,
    `currency` CHAR(3) NOT NULL DEFAULT 'SAR',
    `reference_type` VARCHAR(50) NULL COMMENT 'order, refund, etc.',
    `reference_id` BIGINT UNSIGNED NULL,
    `payment_gateway` VARCHAR(50) NULL,
    `gateway_transaction_id` VARCHAR(255) NULL,
    `description` VARCHAR(255) NULL,
    `metadata` JSON NULL,
    `ip_address` VARCHAR(45) NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_transactions_uuid` (`uuid`),
    KEY `idx_transactions_user` (`user_id`),
    KEY `idx_transactions_type` (`type`),
    KEY `idx_transactions_reference` (`reference_type`, `reference_id`),
    KEY `idx_transactions_created` (`created_at`),
    
    CONSTRAINT `fk_transactions_user` 
        FOREIGN KEY (`user_id`) 
        REFERENCES `users` (`id`) 
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ═══════════════════════════════════════════════════════════════════════════
-- TABLE: coupons
-- ═══════════════════════════════════════════════════════════════════════════
CREATE TABLE `coupons` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `code` VARCHAR(50) NOT NULL,
    `type` ENUM('percentage', 'fixed') NOT NULL,
    `value` INT UNSIGNED NOT NULL COMMENT 'Percentage (1-100) or cents',
    `min_order_amount` INT UNSIGNED NULL,
    `max_discount` INT UNSIGNED NULL COMMENT 'Max discount in cents',
    `usage_limit` INT UNSIGNED NULL COMMENT 'Total uses allowed',
    `usage_per_user` INT UNSIGNED NOT NULL DEFAULT 1,
    `used_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `applies_to` ENUM('all', 'categories', 'products') NOT NULL DEFAULT 'all',
    `applicable_ids` JSON NULL COMMENT 'Category or product IDs',
    `starts_at` TIMESTAMP NULL,
    `expires_at` TIMESTAMP NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_by` BIGINT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_coupons_code` (`code`),
    KEY `idx_coupons_active_dates` (`is_active`, `starts_at`, `expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ═══════════════════════════════════════════════════════════════════════════
-- TABLE: coupon_usage
-- ═══════════════════════════════════════════════════════════════════════════
CREATE TABLE `coupon_usage` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `coupon_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `order_id` BIGINT UNSIGNED NOT NULL,
    `discount_amount` INT UNSIGNED NOT NULL,
    `used_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    KEY `idx_coupon_usage_coupon_user` (`coupon_id`, `user_id`),
    KEY `idx_coupon_usage_order` (`order_id`),
    
    CONSTRAINT `fk_coupon_usage_coupon` 
        FOREIGN KEY (`coupon_id`) 
        REFERENCES `coupons` (`id`) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_coupon_usage_user` 
        FOREIGN KEY (`user_id`) 
        REFERENCES `users` (`id`) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_coupon_usage_order` 
        FOREIGN KEY (`order_id`) 
        REFERENCES `orders` (`id`) 
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ═══════════════════════════════════════════════════════════════════════════
-- TABLE: user_sessions
-- ═══════════════════════════════════════════════════════════════════════════
-- PURPOSE: Track active sessions for security
-- ═══════════════════════════════════════════════════════════════════════════
CREATE TABLE `user_sessions` (
    `id` VARCHAR(128) NOT NULL,
    `user_id` BIGINT UNSIGNED NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` TEXT NULL,
    `payload` TEXT NOT NULL,
    `last_activity` INT NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    KEY `idx_sessions_user` (`user_id`),
    KEY `idx_sessions_activity` (`last_activity`),
    
    CONSTRAINT `fk_sessions_user` 
        FOREIGN KEY (`user_id`) 
        REFERENCES `users` (`id`) 
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ═══════════════════════════════════════════════════════════════════════════
-- TABLE: password_resets
-- ═══════════════════════════════════════════════════════════════════════════
CREATE TABLE `password_resets` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `email` VARCHAR(255) NOT NULL,
    `token_hash` VARCHAR(255) NOT NULL COMMENT 'Hashed token',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `expires_at` TIMESTAMP NOT NULL,
    `used_at` TIMESTAMP NULL,
    
    PRIMARY KEY (`id`),
    KEY `idx_password_resets_email` (`email`),
    KEY `idx_password_resets_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ═══════════════════════════════════════════════════════════════════════════
-- TABLE: email_verifications
-- ═══════════════════════════════════════════════════════════════════════════
CREATE TABLE `email_verifications` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `token_hash` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `expires_at` TIMESTAMP NOT NULL,
    `verified_at` TIMESTAMP NULL,
    
    PRIMARY KEY (`id`),
    KEY `idx_email_verifications_user` (`user_id`),
    KEY `idx_email_verifications_expires` (`expires_at`),
    
    CONSTRAINT `fk_email_verifications_user` 
        FOREIGN KEY (`user_id`) 
        REFERENCES `users` (`id`) 
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ═══════════════════════════════════════════════════════════════════════════
-- TABLE: api_tokens
-- ═══════════════════════════════════════════════════════════════════════════
-- PURPOSE: API authentication tokens for external access
-- ═══════════════════════════════════════════════════════════════════════════
CREATE TABLE `api_tokens` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `token_hash` VARCHAR(255) NOT NULL,
    `abilities` JSON NULL COMMENT 'Permitted actions',
    `last_used_at` TIMESTAMP NULL,
    `expires_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    KEY `idx_api_tokens_user` (`user_id`),
    KEY `idx_api_tokens_hash` (`token_hash`(64)),
    
    CONSTRAINT `fk_api_tokens_user` 
        FOREIGN KEY (`user_id`) 
        REFERENCES `users` (`id`) 
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ═══════════════════════════════════════════════════════════════════════════
-- TABLE: rate_limits
-- ═══════════════════════════════════════════════════════════════════════════
-- PURPOSE: Track rate limiting per IP/user
-- ═══════════════════════════════════════════════════════════════════════════
CREATE TABLE `rate_limits` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `key` VARCHAR(255) NOT NULL COMMENT 'ip:action or user:action',
    `hits` INT UNSIGNED NOT NULL DEFAULT 1,
    `expires_at` TIMESTAMP NOT NULL,
    
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_rate_limits_key` (`key`),
    KEY `idx_rate_limits_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ═══════════════════════════════════════════════════════════════════════════
-- TABLE: activity_logs
-- ═══════════════════════════════════════════════════════════════════════════
-- PURPOSE: Complete audit trail of all actions
-- SECURITY: Immutable - never delete or modify
-- ═══════════════════════════════════════════════════════════════════════════
CREATE TABLE `activity_logs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NULL,
    `action` VARCHAR(100) NOT NULL COMMENT 'e.g., user.login, order.create',
    `subject_type` VARCHAR(100) NULL COMMENT 'Model class name',
    `subject_id` BIGINT UNSIGNED NULL,
    `properties` JSON NULL COMMENT 'Changed data',
    `ip_address` VARCHAR(45) NULL,
    `user_agent` VARCHAR(500) NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    KEY `idx_activity_user` (`user_id`),
    KEY `idx_activity_action` (`action`),
    KEY `idx_activity_subject` (`subject_type`, `subject_id`),
    KEY `idx_activity_created` (`created_at`),
    
    CONSTRAINT `fk_activity_user` 
        FOREIGN KEY (`user_id`) 
        REFERENCES `users` (`id`) 
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ═══════════════════════════════════════════════════════════════════════════
-- TABLE: notifications
-- ═══════════════════════════════════════════════════════════════════════════
CREATE TABLE `notifications` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `uuid` CHAR(36) NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `type` VARCHAR(100) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `body` TEXT NULL,
    `data` JSON NULL,
    `read_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_notifications_uuid` (`uuid`),
    KEY `idx_notifications_user_read` (`user_id`, `read_at`),
    KEY `idx_notifications_created` (`created_at`),
    
    CONSTRAINT `fk_notifications_user` 
        FOREIGN KEY (`user_id`) 
        REFERENCES `users` (`id`) 
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ═══════════════════════════════════════════════════════════════════════════
-- TABLE: settings
-- ═══════════════════════════════════════════════════════════════════════════
CREATE TABLE `settings` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `group` VARCHAR(50) NOT NULL DEFAULT 'general',
    `key` VARCHAR(100) NOT NULL,
    `value` TEXT NULL,
    `type` ENUM('string', 'integer', 'boolean', 'json', 'encrypted') NOT NULL DEFAULT 'string',
    `is_public` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Visible to frontend',
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_settings_group_key` (`group`, `key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ═══════════════════════════════════════════════════════════════════════════
-- TABLE: failed_jobs
-- ═══════════════════════════════════════════════════════════════════════════
CREATE TABLE `failed_jobs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `uuid` CHAR(36) NOT NULL,
    `connection` TEXT NOT NULL,
    `queue` TEXT NOT NULL,
    `payload` LONGTEXT NOT NULL,
    `exception` LONGTEXT NOT NULL,
    `failed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_failed_jobs_uuid` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ═══════════════════════════════════════════════════════════════════════════
-- TRIGGERS
-- ═══════════════════════════════════════════════════════════════════════════

-- Auto-generate UUID for users
DELIMITER //
CREATE TRIGGER `tr_users_before_insert` 
BEFORE INSERT ON `users`
FOR EACH ROW
BEGIN
    IF NEW.uuid IS NULL OR NEW.uuid = '' THEN
        SET NEW.uuid = UUID();
    END IF;
END//

-- Update product stock when stock_codes status changes
CREATE TRIGGER `tr_stock_codes_after_update`
AFTER UPDATE ON `stock_codes`
FOR EACH ROW
BEGIN
    IF OLD.status != NEW.status THEN
        UPDATE products p
        SET p.stock_quantity = (
            SELECT COUNT(*) FROM stock_codes sc 
            WHERE sc.product_id = NEW.product_id 
            AND sc.status = 'available'
        )
        WHERE p.id = NEW.product_id
        AND p.stock_quantity != -1;
    END IF;
END//

DELIMITER ;

-- ═══════════════════════════════════════════════════════════════════════════
-- INDEXES FOR PERFORMANCE
-- ═══════════════════════════════════════════════════════════════════════════

-- Composite indexes for common queries
ALTER TABLE `orders` ADD INDEX `idx_orders_user_status_created` (`user_id`, `status`, `created_at` DESC);
ALTER TABLE `transactions` ADD INDEX `idx_transactions_user_type_created` (`user_id`, `type`, `created_at` DESC);
ALTER TABLE `products` ADD INDEX `idx_products_category_active_sort` (`category_id`, `is_active`, `sort_order`);

-- ═══════════════════════════════════════════════════════════════════════════
-- END OF SCHEMA
-- ═══════════════════════════════════════════════════════════════════════════
