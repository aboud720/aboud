<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * ABOUD STORE - Category Model
 * ═══════════════════════════════════════════════════════════════════════════
 */

namespace Models;

use Core\Model;

if (!defined('ABOUD_STORE')) {
    die('Direct access not allowed');
}

class Category extends Model
{
    /**
     * Table name
     */
    protected static string $table = 'categories';
    
    /**
     * Fillable fields
     */
    protected static array $fillable = [
        'uuid',
        'parent_id',
        'name_ar',
        'name_en',
        'slug',
        'description_ar',
        'description_en',
        'icon',
        'image',
        'sort_order',
        'is_active',
        'meta_title',
        'meta_description',
    ];
    
    // ═══════════════════════════════════════════════════════════════════════
    // RELATIONSHIPS
    // ═══════════════════════════════════════════════════════════════════════
    
    /**
     * Get parent category
     */
    public function parent(): ?self
    {
        if (!$this->parent_id) {
            return null;
        }
        return static::find($this->parent_id);
    }
    
    /**
     * Get child categories
     */
    public function children(): array
    {
        return static::where('parent_id', $this->id)
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->get();
    }
    
    /**
     * Get products
     */
    public function products(): array
    {
        return Product::getByCategory($this->id);
    }
    
    /**
     * Get products count
     */
    public function productsCount(): int
    {
        $result = static::db()->selectOne(
            "SELECT COUNT(*) as count FROM products 
             WHERE category_id = ? AND is_active = 1 AND deleted_at IS NULL",
            [$this->id]
        );
        return (int) ($result['count'] ?? 0);
    }
    
    // ═══════════════════════════════════════════════════════════════════════
    // QUERIES
    // ═══════════════════════════════════════════════════════════════════════
    
    /**
     * Get all root categories
     */
    public static function getRoots(): array
    {
        return static::where('parent_id', 'IS', null)
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->get();
    }
    
    /**
     * Get active categories with children
     */
    public static function getTree(): array
    {
        $roots = static::getRoots();
        
        foreach ($roots as $root) {
            $root->children = $root->children();
        }
        
        return $roots;
    }
    
    /**
     * Find by slug
     */
    public static function findBySlug(string $slug): ?self
    {
        return static::findBy('slug', $slug);
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
     * Convert to array
     */
    public function toArray(): array
    {
        $array = parent::toArray();
        $array['products_count'] = $this->productsCount();
        return $array;
    }
}
