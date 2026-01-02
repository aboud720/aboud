<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * ABOUD STORE - Page Controller
 * ═══════════════════════════════════════════════════════════════════════════
 */

namespace Controllers;

use Models\Category;

if (!defined('ABOUD_STORE')) {
    die('Direct access not allowed');
}

class PageController extends BaseController
{
    /**
     * Home page
     */
    public function home(): string
    {
        $categories = Category::where('is_active', 1)
            ->where('parent_id', 'IS', null)
            ->orderBy('sort_order')
            ->get();
        
        return $this->view('pages.home', [
            'categories' => array_map(fn($c) => $c->toArray(), $categories)
        ]);
    }
    
    /**
     * About page
     */
    public function about(): string
    {
        return $this->view('pages.about');
    }
    
    /**
     * Contact page
     */
    public function contact(): string
    {
        return $this->view('pages.contact');
    }
    
    /**
     * Terms page
     */
    public function terms(): string
    {
        return $this->view('pages.terms');
    }
    
    /**
     * Privacy page
     */
    public function privacy(): string
    {
        return $this->view('pages.privacy');
    }
}
