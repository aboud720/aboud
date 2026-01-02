<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * ABOUD STORE - Product API Controller
 * ═══════════════════════════════════════════════════════════════════════════
 */

namespace Api;

use Controllers\BaseController;
use Core\Response;
use Models\Product;
use Models\Category;

if (!defined('ABOUD_STORE')) {
    die('Direct access not allowed');
}

class ProductApiController extends BaseController
{
    /**
     * GET /api/products
     * List all products with pagination
     */
    public function index(): void
    {
        $page = (int) ($this->input('page') ?? 1);
        $perPage = min((int) ($this->input('per_page') ?? 15), 50);
        $categoryId = $this->input('category_id');
        $type = $this->input('type');
        $featured = $this->input('featured');
        $search = $this->input('search');
        
        $query = Product::where('is_active', 1);
        
        if ($categoryId) {
            $query->where('category_id', (int) $categoryId);
        }
        
        if ($type) {
            $query->where('type', $type);
        }
        
        if ($featured) {
            $query->where('is_featured', 1);
        }
        
        if ($search) {
            $products = Product::search($search, $perPage);
            Response::json([
                'success' => true,
                'data' => array_map(fn($p) => $p->toArray(), $products)
            ]);
            return;
        }
        
        $result = $query->orderBy('sort_order')->paginate($perPage, $page);
        
        Response::paginated(
            array_map(fn($p) => $p->toArray(), $result['data']),
            $result['total'],
            $result['page'],
            $result['per_page']
        );
    }
    
    /**
     * GET /api/products/featured
     * Get featured products
     */
    public function featured(): void
    {
        $limit = min((int) ($this->input('limit') ?? 10), 20);
        $products = Product::getFeatured($limit);
        
        $this->success(
            array_map(fn($p) => $p->toArray(), $products)
        );
    }
    
    /**
     * GET /api/products/{uuid}
     * Get single product
     */
    public function show(array $params): void
    {
        $uuid = $params['uuid'] ?? null;
        
        if (!$uuid) {
            Response::notFound('Product not found');
        }
        
        $product = Product::findByUuid($uuid);
        
        if (!$product || !$product->is_active) {
            Response::notFound('Product not found');
        }
        
        $data = $product->toArray();
        $data['variants'] = $product->variants();
        $data['category'] = $product->category()?->toArray();
        
        $this->success($data);
    }
    
    /**
     * GET /api/products/{uuid}/variants
     * Get product variants
     */
    public function variants(array $params): void
    {
        $uuid = $params['uuid'] ?? null;
        
        if (!$uuid) {
            Response::notFound('Product not found');
        }
        
        $product = Product::findByUuid($uuid);
        
        if (!$product || !$product->is_active) {
            Response::notFound('Product not found');
        }
        
        $this->success($product->variants());
    }
    
    /**
     * GET /api/categories
     * List all categories
     */
    public function categories(): void
    {
        $tree = $this->input('tree') === '1';
        
        if ($tree) {
            $categories = Category::getTree();
            $data = array_map(function($cat) {
                $arr = $cat->toArray();
                $arr['children'] = array_map(fn($c) => $c->toArray(), $cat->children ?? []);
                return $arr;
            }, $categories);
        } else {
            $categories = Category::where('is_active', 1)
                ->orderBy('sort_order')
                ->get();
            $data = array_map(fn($c) => $c->toArray(), $categories);
        }
        
        $this->success($data);
    }
    
    /**
     * GET /api/categories/{slug}
     * Get category by slug
     */
    public function category(array $params): void
    {
        $slug = $params['slug'] ?? null;
        
        if (!$slug) {
            Response::notFound('Category not found');
        }
        
        $category = Category::findBySlug($slug);
        
        if (!$category || !$category->is_active) {
            Response::notFound('Category not found');
        }
        
        $data = $category->toArray();
        $data['children'] = array_map(fn($c) => $c->toArray(), $category->children());
        
        $this->success($data);
    }
    
    /**
     * GET /api/categories/{slug}/products
     * Get products by category
     */
    public function categoryProducts(array $params): void
    {
        $slug = $params['slug'] ?? null;
        
        if (!$slug) {
            Response::notFound('Category not found');
        }
        
        $category = Category::findBySlug($slug);
        
        if (!$category || !$category->is_active) {
            Response::notFound('Category not found');
        }
        
        $page = (int) ($this->input('page') ?? 1);
        $perPage = min((int) ($this->input('per_page') ?? 15), 50);
        
        $result = Product::where('category_id', $category->id)
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->paginate($perPage, $page);
        
        Response::paginated(
            array_map(fn($p) => $p->toArray(), $result['data']),
            $result['total'],
            $result['page'],
            $result['per_page']
        );
    }
    
    /**
     * GET /api/search
     * Search products
     */
    public function search(): void
    {
        $query = $this->input('q');
        
        if (!$query || strlen($query) < 2) {
            $this->error('Search query must be at least 2 characters', 'INVALID_QUERY', 400);
        }
        
        $limit = min((int) ($this->input('limit') ?? 20), 50);
        $products = Product::search($query, $limit);
        
        $this->success(
            array_map(fn($p) => $p->toArray(), $products)
        );
    }
}
