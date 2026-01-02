<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * ABOUD STORE - Product Model
 * ═══════════════════════════════════════════════════════════════════════════
 */

namespace Models;

use Core\Model;
use Core\Application;

if (!defined('ABOUD_STORE')) {
    die('Direct access not allowed');
}

class Product extends Model
{
    /**
     * Table name
     */
    protected static string $table = 'products';
    
    /**
     * Fillable fields
     */
    protected static array $fillable = [
        'uuid',
        'category_id',
        'vendor_id',
        'name_ar',
        'name_en',
        'slug',
        'description_ar',
        'description_en',
        'type',
        'image',
        'gallery',
        'base_price',
        'sale_price',
        'cost_price',
        'currency',
        'stock_quantity',
        'min_quantity',
        'max_quantity',
        'requires_player_id',
        'player_id_label',
        'requires_server',
        'servers',
        'delivery_type',
        'api_provider',
        'api_product_id',
        'is_featured',
        'is_active',
        'sort_order',
    ];
    
    /**
     * Hidden fields
     */
    protected static array $hidden = [
        'cost_price',
        'api_provider',
        'api_product_id',
    ];
    
    /**
     * Soft deletes
     */
    protected static bool $softDeletes = true;
    
    /**
     * Product types
     */
    public const TYPE_GAME_CARD = 'game_card';
    public const TYPE_APP_SUBSCRIPTION = 'app_subscription';
    public const TYPE_IN_GAME_CURRENCY = 'in_game_currency';
    public const TYPE_ACCOUNT = 'account';
    public const TYPE_SERVICE = 'service';
    
    /**
     * Delivery types
     */
    public const DELIVERY_INSTANT = 'instant';
    public const DELIVERY_MANUAL = 'manual';
    public const DELIVERY_API = 'api';
    
    // ═══════════════════════════════════════════════════════════════════════
    // RELATIONSHIPS
    // ═══════════════════════════════════════════════════════════════════════
    
    /**
     * Get category
     */
    public function category(): ?Category
    {
        return Category::find($this->category_id);
    }
    
    /**
     * Get vendor
     */
    public function vendor(): ?User
    {
        if (!$this->vendor_id) {
            return null;
        }
        return User::find($this->vendor_id);
    }
    
    /**
     * Get variants
     */
    public function variants(): array
    {
        return static::db()->select(
            "SELECT * FROM product_variants 
             WHERE product_id = ? AND is_active = 1 
             ORDER BY sort_order, price",
            [$this->id]
        );
    }
    
    // ═══════════════════════════════════════════════════════════════════════
    // PRICE HELPERS
    // ═══════════════════════════════════════════════════════════════════════
    
    /**
     * Get display price (sale price if available)
     */
    public function getPrice(): int
    {
        return $this->sale_price ?? $this->base_price;
    }
    
    /**
     * Get formatted price
     */
    public function getFormattedPrice(): string
    {
        $price = $this->getPrice() / 100;
        return number_format($price, 2) . ' ر.س';
    }
    
    /**
     * Get formatted original price
     */
    public function getFormattedBasePrice(): string
    {
        return number_format($this->base_price / 100, 2) . ' ر.س';
    }
    
    /**
     * Check if on sale
     */
    public function isOnSale(): bool
    {
        return $this->sale_price !== null && $this->sale_price < $this->base_price;
    }
    
    /**
     * Get discount percentage
     */
    public function getDiscountPercent(): int
    {
        if (!$this->isOnSale()) {
            return 0;
        }
        return (int) round((($this->base_price - $this->sale_price) / $this->base_price) * 100);
    }
    
    // ═══════════════════════════════════════════════════════════════════════
    // STOCK MANAGEMENT
    // ═══════════════════════════════════════════════════════════════════════
    
    /**
     * Check if in stock
     */
    public function inStock(): bool
    {
        // -1 means unlimited stock
        if ($this->stock_quantity === -1) {
            return true;
        }
        return $this->stock_quantity > 0;
    }
    
    /**
     * Get available stock count
     */
    public function getAvailableStock(): int
    {
        if ($this->stock_quantity === -1) {
            return PHP_INT_MAX;
        }
        return $this->stock_quantity;
    }
    
    /**
     * Reserve stock for order
     */
    public function reserveStock(int $quantity, int $orderId): bool
    {
        if ($this->delivery_type === self::DELIVERY_INSTANT) {
            return static::db()->transaction(function($db) use ($quantity, $orderId) {
                // Get available codes
                $codes = $db->select(
                    "SELECT id FROM stock_codes 
                     WHERE product_id = ? AND status = 'available' 
                     ORDER BY created_at 
                     LIMIT ?",
                    [$this->id, $quantity]
                );
                
                if (count($codes) < $quantity) {
                    return false;
                }
                
                // Reserve codes
                $codeIds = array_column($codes, 'id');
                $placeholders = implode(',', array_fill(0, count($codeIds), '?'));
                
                $db->query(
                    "UPDATE stock_codes 
                     SET status = 'reserved', 
                         reserved_until = DATE_ADD(NOW(), INTERVAL 15 MINUTE),
                         reserved_by_order = ?
                     WHERE id IN ({$placeholders})",
                    array_merge([$orderId], $codeIds)
                );
                
                return true;
            });
        }
        
        // For non-instant delivery, just check quantity
        if ($this->stock_quantity !== -1) {
            if ($this->stock_quantity < $quantity) {
                return false;
            }
            $this->stock_quantity -= $quantity;
            $this->save();
        }
        
        return true;
    }
    
    /**
     * Release reserved stock
     */
    public function releaseStock(int $orderId): void
    {
        static::db()->query(
            "UPDATE stock_codes 
             SET status = 'available', 
                 reserved_until = NULL,
                 reserved_by_order = NULL
             WHERE product_id = ? AND reserved_by_order = ?",
            [$this->id, $orderId]
        );
    }
    
    // ═══════════════════════════════════════════════════════════════════════
    // QUERIES
    // ═══════════════════════════════════════════════════════════════════════
    
    /**
     * Get active products
     */
    public static function getActive(): array
    {
        return static::where('is_active', 1)
            ->orderBy('sort_order')
            ->get();
    }
    
    /**
     * Get featured products
     */
    public static function getFeatured(int $limit = 10): array
    {
        return static::where('is_featured', 1)
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->limit($limit)
            ->get();
    }
    
    /**
     * Get products by category
     */
    public static function getByCategory(int $categoryId): array
    {
        return static::where('category_id', $categoryId)
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->get();
    }
    
    /**
     * Search products
     */
    public static function search(string $query, int $limit = 20): array
    {
        $searchQuery = '%' . $query . '%';
        
        $results = static::db()->select(
            "SELECT * FROM products 
             WHERE is_active = 1 
             AND deleted_at IS NULL
             AND (name_ar LIKE ? OR name_en LIKE ? OR description_ar LIKE ? OR description_en LIKE ?)
             ORDER BY 
                CASE WHEN name_ar LIKE ? OR name_en LIKE ? THEN 1 ELSE 2 END,
                total_sales DESC
             LIMIT ?",
            [$searchQuery, $searchQuery, $searchQuery, $searchQuery, $searchQuery, $searchQuery, $limit]
        );
        
        return array_map(fn($row) => new static($row), $results);
    }
    
    // ═══════════════════════════════════════════════════════════════════════
    // RATINGS
    // ═══════════════════════════════════════════════════════════════════════
    
    /**
     * Get average rating
     */
    public function getRating(): float
    {
        if ($this->rating_count === 0) {
            return 0;
        }
        return round($this->rating_sum / $this->rating_count, 1);
    }
    
    /**
     * Add rating
     */
    public function addRating(int $rating): void
    {
        $this->rating_sum += $rating;
        $this->rating_count++;
        $this->save();
    }
    
    // ═══════════════════════════════════════════════════════════════════════
    // LOCALIZATION
    // ═══════════════════════════════════════════════════════════════════════
    
    /**
     * Get localized name
     */
    public function getName(string $locale = 'ar'): string
    {
        return $locale === 'ar' ? $this->name_ar : $this->name_en;
    }
    
    /**
     * Get localized description
     */
    public function getDescription(string $locale = 'ar'): ?string
    {
        return $locale === 'ar' ? $this->description_ar : $this->description_en;
    }
    
    // ═══════════════════════════════════════════════════════════════════════
    // SERIALIZATION
    // ═══════════════════════════════════════════════════════════════════════
    
    /**
     * Convert to array (with computed fields)
     */
    public function toArray(): array
    {
        $array = parent::toArray();
        
        // Add computed fields
        $array['formatted_price'] = $this->getFormattedPrice();
        $array['is_on_sale'] = $this->isOnSale();
        $array['discount_percent'] = $this->getDiscountPercent();
        $array['in_stock'] = $this->inStock();
        $array['rating'] = $this->getRating();
        
        // Parse JSON fields
        if (isset($array['gallery']) && is_string($array['gallery'])) {
            $array['gallery'] = json_decode($array['gallery'], true) ?? [];
        }
        if (isset($array['servers']) && is_string($array['servers'])) {
            $array['servers'] = json_decode($array['servers'], true) ?? [];
        }
        
        return $array;
    }
}
