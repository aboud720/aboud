/**
 * ═══════════════════════════════════════════════════════════════════════════
 * ABOUD STORE - Main JavaScript
 * ═══════════════════════════════════════════════════════════════════════════
 */

'use strict';

// ═══════════════════════════════════════════════════════════════════════════
// App Configuration
// ═══════════════════════════════════════════════════════════════════════════
const App = {
    apiBaseUrl: '/api/v1',
    csrfToken: null,
    
    /**
     * Initialize application
     */
    init() {
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        this.initNavbar();
        this.initAlerts();
        this.initForms();
        this.initDropdowns();
    },
    
    /**
     * Get CSRF token
     */
    getCsrfToken() {
        return this.csrfToken;
    }
};

// ═══════════════════════════════════════════════════════════════════════════
// API Client
// ═══════════════════════════════════════════════════════════════════════════
const ApiClient = {
    /**
     * Make API request
     */
    async request(method, endpoint, data = null, options = {}) {
        const url = App.apiBaseUrl + endpoint;
        const config = {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-Token': App.getCsrfToken(),
                ...options.headers
            }
        };
        
        // Add auth token if available
        const token = localStorage.getItem('auth_token');
        if (token) {
            config.headers['Authorization'] = `Bearer ${token}`;
        }
        
        if (data && method !== 'GET') {
            config.body = JSON.stringify(data);
        }
        
        try {
            const response = await fetch(url, config);
            const result = await response.json();
            
            if (!response.ok) {
                throw result;
            }
            
            return result;
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    },
    
    get(endpoint, options = {}) {
        return this.request('GET', endpoint, null, options);
    },
    
    post(endpoint, data, options = {}) {
        return this.request('POST', endpoint, data, options);
    },
    
    put(endpoint, data, options = {}) {
        return this.request('PUT', endpoint, data, options);
    },
    
    delete(endpoint, options = {}) {
        return this.request('DELETE', endpoint, null, options);
    }
};

// ═══════════════════════════════════════════════════════════════════════════
// UI Components
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Initialize navbar functionality
 */
App.initNavbar = function() {
    const toggle = document.getElementById('navbarToggle');
    const menu = document.querySelector('.navbar-menu');
    
    if (toggle && menu) {
        toggle.addEventListener('click', () => {
            menu.classList.toggle('active');
        });
    }
};

/**
 * Initialize auto-dismiss alerts
 */
App.initAlerts = function() {
    const alerts = document.querySelectorAll('.alert');
    
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
};

/**
 * Initialize form validation
 */
App.initForms = function() {
    document.querySelectorAll('form[data-validate]').forEach(form => {
        form.addEventListener('submit', function(e) {
            let valid = true;
            
            // Clear previous errors
            form.querySelectorAll('.is-invalid').forEach(el => {
                el.classList.remove('is-invalid');
            });
            form.querySelectorAll('.invalid-feedback').forEach(el => {
                el.remove();
            });
            
            // Validate required fields
            form.querySelectorAll('[required]').forEach(field => {
                if (!field.value.trim()) {
                    valid = false;
                    showFieldError(field, 'هذا الحقل مطلوب');
                }
            });
            
            // Validate email fields
            form.querySelectorAll('[type="email"]').forEach(field => {
                if (field.value && !isValidEmail(field.value)) {
                    valid = false;
                    showFieldError(field, 'البريد الإلكتروني غير صحيح');
                }
            });
            
            // Validate password confirmation
            const password = form.querySelector('[name="password"]');
            const confirmation = form.querySelector('[name="password_confirmation"]');
            if (password && confirmation && password.value !== confirmation.value) {
                valid = false;
                showFieldError(confirmation, 'كلمة المرور غير متطابقة');
            }
            
            if (!valid) {
                e.preventDefault();
            }
        });
    });
};

/**
 * Show field error
 */
function showFieldError(field, message) {
    field.classList.add('is-invalid');
    const feedback = document.createElement('div');
    feedback.className = 'invalid-feedback';
    feedback.textContent = message;
    field.parentNode.appendChild(feedback);
}

/**
 * Validate email format
 */
function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

/**
 * Initialize dropdowns
 */
App.initDropdowns = function() {
    // Close dropdowns when clicking outside
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.nav-dropdown')) {
            document.querySelectorAll('.nav-dropdown-menu.active').forEach(menu => {
                menu.classList.remove('active');
            });
        }
    });
};

// ═══════════════════════════════════════════════════════════════════════════
// Toast Notifications
// ═══════════════════════════════════════════════════════════════════════════
const Toast = {
    container: null,
    
    init() {
        this.container = document.createElement('div');
        this.container.id = 'toast-container';
        this.container.style.cssText = `
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 10px;
        `;
        document.body.appendChild(this.container);
    },
    
    show(message, type = 'info', duration = 4000) {
        if (!this.container) this.init();
        
        const toast = document.createElement('div');
        toast.className = `alert alert-${type}`;
        toast.style.cssText = `
            min-width: 300px;
            animation: slideIn 0.3s ease;
        `;
        
        const icons = {
            success: 'check-circle',
            danger: 'exclamation-circle',
            warning: 'exclamation-triangle',
            info: 'info-circle'
        };
        
        toast.innerHTML = `
            <i class="bi bi-${icons[type] || 'info-circle'}"></i>
            <span>${message}</span>
        `;
        
        this.container.appendChild(toast);
        
        setTimeout(() => {
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 300);
        }, duration);
    },
    
    success(message) { this.show(message, 'success'); },
    error(message) { this.show(message, 'danger'); },
    warning(message) { this.show(message, 'warning'); },
    info(message) { this.show(message, 'info'); }
};

// ═══════════════════════════════════════════════════════════════════════════
// Cart Management
// ═══════════════════════════════════════════════════════════════════════════
const Cart = {
    items: [],
    
    init() {
        const saved = localStorage.getItem('cart');
        if (saved) {
            try {
                this.items = JSON.parse(saved);
            } catch (e) {
                this.items = [];
            }
        }
        this.updateUI();
    },
    
    add(productId, variantId = null, quantity = 1, playerId = null, server = null) {
        const existingIndex = this.items.findIndex(
            item => item.product_id === productId && item.variant_id === variantId
        );
        
        if (existingIndex > -1) {
            this.items[existingIndex].quantity += quantity;
        } else {
            this.items.push({
                product_id: productId,
                variant_id: variantId,
                quantity: quantity,
                player_id: playerId,
                server: server
            });
        }
        
        this.save();
        this.updateUI();
        Toast.success('تمت الإضافة إلى السلة');
    },
    
    remove(index) {
        this.items.splice(index, 1);
        this.save();
        this.updateUI();
    },
    
    updateQuantity(index, quantity) {
        if (quantity < 1) {
            this.remove(index);
            return;
        }
        this.items[index].quantity = quantity;
        this.save();
        this.updateUI();
    },
    
    clear() {
        this.items = [];
        this.save();
        this.updateUI();
    },
    
    save() {
        localStorage.setItem('cart', JSON.stringify(this.items));
    },
    
    updateUI() {
        const countEl = document.getElementById('cart-count');
        if (countEl) {
            countEl.textContent = this.items.reduce((sum, item) => sum + item.quantity, 0);
        }
    },
    
    getItems() {
        return this.items;
    },
    
    isEmpty() {
        return this.items.length === 0;
    }
};

// ═══════════════════════════════════════════════════════════════════════════
// Auth Management
// ═══════════════════════════════════════════════════════════════════════════
const Auth = {
    async login(email, password) {
        try {
            const response = await ApiClient.post('/auth/login', { email, password });
            
            if (response.success) {
                localStorage.setItem('auth_token', response.data.access_token);
                localStorage.setItem('refresh_token', response.data.refresh_token);
                localStorage.setItem('user', JSON.stringify(response.data.user));
                return response;
            }
        } catch (error) {
            throw error;
        }
    },
    
    async logout() {
        try {
            await ApiClient.post('/auth/logout');
        } catch (e) {
            // Ignore errors
        }
        
        localStorage.removeItem('auth_token');
        localStorage.removeItem('refresh_token');
        localStorage.removeItem('user');
        window.location.href = '/login';
    },
    
    getToken() {
        return localStorage.getItem('auth_token');
    },
    
    getUser() {
        const user = localStorage.getItem('user');
        return user ? JSON.parse(user) : null;
    },
    
    isLoggedIn() {
        return !!this.getToken();
    },
    
    async refreshToken() {
        const refreshToken = localStorage.getItem('refresh_token');
        if (!refreshToken) return false;
        
        try {
            const response = await ApiClient.post('/auth/refresh', {
                refresh_token: refreshToken
            });
            
            if (response.success) {
                localStorage.setItem('auth_token', response.data.access_token);
                localStorage.setItem('refresh_token', response.data.refresh_token);
                return true;
            }
        } catch (e) {
            this.logout();
            return false;
        }
    }
};

// ═══════════════════════════════════════════════════════════════════════════
// Utility Functions
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Format price
 */
function formatPrice(priceInCents) {
    return (priceInCents / 100).toFixed(2) + ' ر.س';
}

/**
 * Format date
 */
function formatDate(dateString) {
    return new Date(dateString).toLocaleDateString('ar-SA', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

/**
 * Format relative time
 */
function formatRelativeTime(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diff = Math.floor((now - date) / 1000);
    
    if (diff < 60) return 'الآن';
    if (diff < 3600) return `منذ ${Math.floor(diff / 60)} دقيقة`;
    if (diff < 86400) return `منذ ${Math.floor(diff / 3600)} ساعة`;
    if (diff < 604800) return `منذ ${Math.floor(diff / 86400)} يوم`;
    
    return formatDate(dateString);
}

/**
 * Debounce function
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Copy to clipboard
 */
async function copyToClipboard(text) {
    try {
        await navigator.clipboard.writeText(text);
        Toast.success('تم النسخ');
    } catch (e) {
        // Fallback
        const textarea = document.createElement('textarea');
        textarea.value = text;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        Toast.success('تم النسخ');
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// Product Functions
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Load featured products
 */
async function loadFeaturedProducts() {
    const container = document.getElementById('featured-products');
    if (!container) return;
    
    try {
        container.innerHTML = '<div class="text-center"><div class="spinner"></div></div>';
        
        const response = await ApiClient.get('/products/featured?limit=8');
        
        if (response.success && response.data.length > 0) {
            container.innerHTML = response.data.map(product => createProductCard(product)).join('');
        } else {
            container.innerHTML = '<p class="text-center text-muted">لا توجد منتجات مميزة</p>';
        }
    } catch (error) {
        container.innerHTML = '<p class="text-center text-danger">حدث خطأ في تحميل المنتجات</p>';
    }
}

/**
 * Create product card HTML
 */
function createProductCard(product) {
    const badge = product.is_on_sale 
        ? `<span class="product-badge">-${product.discount_percent}%</span>` 
        : '';
    
    const originalPrice = product.is_on_sale
        ? `<span class="price-original">${formatPrice(product.base_price)}</span>`
        : '';
    
    return `
        <a href="/products/${product.slug}" class="card product-card">
            ${badge}
            <img src="${product.image || '/images/placeholder.png'}" alt="${product.name_ar}" class="card-img">
            <div class="card-body">
                <h3 class="card-title">${product.name_ar}</h3>
                <div class="product-price">
                    <span class="price-current">${product.formatted_price}</span>
                    ${originalPrice}
                </div>
            </div>
        </a>
    `;
}

// ═══════════════════════════════════════════════════════════════════════════
// Search
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Initialize search autocomplete
 */
function initSearchAutocomplete() {
    const searchInput = document.querySelector('.search-input');
    if (!searchInput) return;
    
    const searchResults = document.createElement('div');
    searchResults.className = 'search-results';
    searchInput.parentNode.appendChild(searchResults);
    
    searchInput.addEventListener('input', debounce(async (e) => {
        const query = e.target.value.trim();
        
        if (query.length < 2) {
            searchResults.innerHTML = '';
            searchResults.style.display = 'none';
            return;
        }
        
        try {
            const response = await ApiClient.get(`/search?q=${encodeURIComponent(query)}&limit=5`);
            
            if (response.success && response.data.length > 0) {
                searchResults.innerHTML = response.data.map(product => `
                    <a href="/products/${product.slug}" class="search-result-item">
                        <img src="${product.image || '/images/placeholder.png'}" alt="${product.name_ar}">
                        <div>
                            <div class="search-result-name">${product.name_ar}</div>
                            <div class="search-result-price">${product.formatted_price}</div>
                        </div>
                    </a>
                `).join('');
                searchResults.style.display = 'block';
            } else {
                searchResults.innerHTML = '<div class="search-no-results">لا توجد نتائج</div>';
                searchResults.style.display = 'block';
            }
        } catch (e) {
            searchResults.style.display = 'none';
        }
    }, 300));
    
    // Hide on click outside
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.navbar-search')) {
            searchResults.style.display = 'none';
        }
    });
}

// ═══════════════════════════════════════════════════════════════════════════
// Initialize
// ═══════════════════════════════════════════════════════════════════════════
document.addEventListener('DOMContentLoaded', () => {
    App.init();
    Cart.init();
    initSearchAutocomplete();
    loadFeaturedProducts();
});

// Export for global access
window.App = App;
window.ApiClient = ApiClient;
window.Toast = Toast;
window.Cart = Cart;
window.Auth = Auth;
