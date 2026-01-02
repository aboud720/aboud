<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * ABOUD STORE - Order Model
 * ═══════════════════════════════════════════════════════════════════════════
 */

namespace Models;

use Core\Model;
use Core\Application;

if (!defined('ABOUD_STORE')) {
    die('Direct access not allowed');
}

class Order extends Model
{
    /**
     * Table name
     */
    protected static string $table = 'orders';
    
    /**
     * Fillable fields
     */
    protected static array $fillable = [
        'uuid',
        'order_number',
        'user_id',
        'status',
        'subtotal',
        'discount_amount',
        'coupon_id',
        'tax_amount',
        'total_amount',
        'currency',
        'payment_method',
        'payment_status',
        'paid_at',
        'ip_address',
        'user_agent',
        'notes',
        'admin_notes',
        'processed_by',
        'completed_at',
    ];
    
    /**
     * Hidden fields
     */
    protected static array $hidden = [
        'ip_address',
        'user_agent',
        'admin_notes',
    ];
    
    /**
     * Order statuses
     */
    public const STATUS_PENDING_PAYMENT = 'pending_payment';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_DELIVERING = 'delivering';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REFUNDED = 'refunded';
    public const STATUS_PARTIALLY_REFUNDED = 'partially_refunded';
    
    /**
     * Payment statuses
     */
    public const PAYMENT_PENDING = 'pending';
    public const PAYMENT_PAID = 'paid';
    public const PAYMENT_FAILED = 'failed';
    public const PAYMENT_REFUNDED = 'refunded';
    
    // ═══════════════════════════════════════════════════════════════════════
    // ORDER CREATION
    // ═══════════════════════════════════════════════════════════════════════
    
    /**
     * Generate unique order number
     */
    public static function generateOrderNumber(): string
    {
        $prefix = date('Ymd');
        $random = strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
        return "ORD-{$prefix}-{$random}";
    }
    
    /**
     * Create order from cart
     */
    public static function createFromCart(int $userId, array $cartItems, ?string $couponCode = null): self
    {
        $db = static::db();
        $security = Application::getInstance()->security();
        
        return $db->transaction(function($db) use ($userId, $cartItems, $couponCode, $security) {
            // Calculate totals
            $subtotal = 0;
            foreach ($cartItems as $item) {
                $product = Product::find($item['product_id']);
                if (!$product || !$product->inStock()) {
                    throw new \RuntimeException("Product not available: {$item['product_id']}");
                }
                $subtotal += $product->getPrice() * $item['quantity'];
            }
            
            // Apply coupon if provided
            $discountAmount = 0;
            $couponId = null;
            if ($couponCode) {
                // TODO: Validate and apply coupon
            }
            
            // Calculate tax (15% VAT in Saudi Arabia)
            $taxAmount = (int) round($subtotal * 0.15);
            $totalAmount = $subtotal - $discountAmount + $taxAmount;
            
            // Create order
            $order = new self([
                'uuid' => $db->uuid(),
                'order_number' => self::generateOrderNumber(),
                'user_id' => $userId,
                'status' => self::STATUS_PENDING_PAYMENT,
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'coupon_id' => $couponId,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'currency' => 'SAR',
                'payment_status' => self::PAYMENT_PENDING,
                'ip_address' => $security->getClientIp(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            ]);
            
            $order->save();
            
            // Create order items
            foreach ($cartItems as $item) {
                $product = Product::find($item['product_id']);
                $variant = isset($item['variant_id']) 
                    ? $db->selectOne("SELECT * FROM product_variants WHERE id = ?", [$item['variant_id']])
                    : null;
                
                $unitPrice = $variant ? $variant['price'] : $product->getPrice();
                
                $db->insert('order_items', [
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'variant_id' => $item['variant_id'] ?? null,
                    'product_name' => $product->name_ar,
                    'variant_name' => $variant['name_ar'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit_price' => $unitPrice,
                    'total_price' => $unitPrice * $item['quantity'],
                    'player_id' => $item['player_id'] ?? null,
                    'server' => $item['server'] ?? null,
                    'status' => 'pending',
                ]);
                
                // Reserve stock
                $product->reserveStock($item['quantity'], $order->id);
            }
            
            return $order;
        });
    }
    
    // ═══════════════════════════════════════════════════════════════════════
    // RELATIONSHIPS
    // ═══════════════════════════════════════════════════════════════════════
    
    /**
     * Get user
     */
    public function user(): ?User
    {
        return User::find($this->user_id);
    }
    
    /**
     * Get order items
     */
    public function items(): array
    {
        return static::db()->select(
            "SELECT * FROM order_items WHERE order_id = ?",
            [$this->id]
        );
    }
    
    // ═══════════════════════════════════════════════════════════════════════
    // PAYMENT
    // ═══════════════════════════════════════════════════════════════════════
    
    /**
     * Pay with wallet
     */
    public function payWithWallet(): bool
    {
        $user = $this->user();
        
        if (!$user) {
            return false;
        }
        
        if ($user->balance < $this->total_amount) {
            return false;
        }
        
        return static::db()->transaction(function($db) use ($user) {
            // Deduct from wallet
            $result = $user->deductBalance($this->total_amount, 'purchase', $this->id);
            
            if (!$result) {
                return false;
            }
            
            // Update order
            $this->payment_method = 'wallet';
            $this->payment_status = self::PAYMENT_PAID;
            $this->paid_at = date('Y-m-d H:i:s');
            $this->status = self::STATUS_PROCESSING;
            $this->save();
            
            // Process delivery
            $this->processDelivery();
            
            return true;
        });
    }
    
    /**
     * Process delivery
     */
    public function processDelivery(): void
    {
        $items = $this->items();
        $allDelivered = true;
        
        foreach ($items as $item) {
            $product = Product::find($item['product_id']);
            
            if (!$product) {
                continue;
            }
            
            if ($product->delivery_type === Product::DELIVERY_INSTANT) {
                // Get codes from stock
                $codes = static::db()->select(
                    "SELECT id, code_encrypted FROM stock_codes 
                     WHERE product_id = ? 
                     AND reserved_by_order = ?
                     ORDER BY id
                     LIMIT ?",
                    [$product->id, $this->id, $item['quantity']]
                );
                
                if (count($codes) === $item['quantity']) {
                    $security = Application::getInstance()->security();
                    $decryptedCodes = [];
                    
                    foreach ($codes as $code) {
                        // Decrypt code
                        $decryptedCodes[] = $security->decrypt($code['code_encrypted']);
                        
                        // Mark as sold
                        static::db()->update('stock_codes', [
                            'status' => 'sold',
                            'sold_at' => date('Y-m-d H:i:s'),
                            'sold_to_user' => $this->user_id,
                            'sold_in_order' => $this->id,
                        ], ['id' => $code['id']]);
                    }
                    
                    // Update order item
                    static::db()->update('order_items', [
                        'status' => 'delivered',
                        'delivery_data' => json_encode($decryptedCodes),
                        'delivered_at' => date('Y-m-d H:i:s'),
                    ], ['id' => $item['id']]);
                    
                } else {
                    $allDelivered = false;
                }
                
            } else {
                // Manual or API delivery - mark as pending
                $allDelivered = false;
            }
        }
        
        // Update order status
        if ($allDelivered) {
            $this->status = self::STATUS_COMPLETED;
            $this->completed_at = date('Y-m-d H:i:s');
        } else {
            $this->status = self::STATUS_DELIVERING;
        }
        
        $this->save();
        
        // Update product sales count
        foreach ($items as $item) {
            static::db()->query(
                "UPDATE products SET total_sales = total_sales + ? WHERE id = ?",
                [$item['quantity'], $item['product_id']]
            );
        }
        
        // Create notification
        $this->createNotification();
    }
    
    /**
     * Create order notification
     */
    private function createNotification(): void
    {
        $db = static::db();
        
        $title = $this->status === self::STATUS_COMPLETED 
            ? 'تم إكمال طلبك بنجاح'
            : 'طلبك قيد المعالجة';
        
        $db->insert('notifications', [
            'uuid' => $db->uuid(),
            'user_id' => $this->user_id,
            'type' => 'order_update',
            'title' => $title,
            'body' => "رقم الطلب: {$this->order_number}",
            'data' => json_encode([
                'order_id' => $this->id,
                'order_number' => $this->order_number,
                'status' => $this->status,
            ]),
        ]);
    }
    
    // ═══════════════════════════════════════════════════════════════════════
    // CANCELLATION & REFUND
    // ═══════════════════════════════════════════════════════════════════════
    
    /**
     * Cancel order
     */
    public function cancel(): bool
    {
        if (!in_array($this->status, [self::STATUS_PENDING_PAYMENT, self::STATUS_PROCESSING])) {
            return false;
        }
        
        return static::db()->transaction(function($db) {
            // Release reserved stock
            $items = $this->items();
            foreach ($items as $item) {
                $product = Product::find($item['product_id']);
                if ($product) {
                    $product->releaseStock($this->id);
                }
            }
            
            // Refund if paid
            if ($this->payment_status === self::PAYMENT_PAID) {
                $user = $this->user();
                if ($user) {
                    $user->addBalance($this->total_amount, 'refund', "Refund for order {$this->order_number}");
                }
            }
            
            $this->status = self::STATUS_CANCELLED;
            $this->save();
            
            return true;
        });
    }
    
    // ═══════════════════════════════════════════════════════════════════════
    // QUERIES
    // ═══════════════════════════════════════════════════════════════════════
    
    /**
     * Get user's orders
     */
    public static function getByUser(int $userId, int $limit = 20): array
    {
        return static::where('user_id', $userId)
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->get();
    }
    
    /**
     * Get order by number
     */
    public static function findByNumber(string $orderNumber): ?self
    {
        return static::findBy('order_number', $orderNumber);
    }
    
    // ═══════════════════════════════════════════════════════════════════════
    // HELPERS
    // ═══════════════════════════════════════════════════════════════════════
    
    /**
     * Get formatted total
     */
    public function getFormattedTotal(): string
    {
        return number_format($this->total_amount / 100, 2) . ' ر.س';
    }
    
    /**
     * Get status label (Arabic)
     */
    public function getStatusLabel(): string
    {
        $labels = [
            self::STATUS_PENDING_PAYMENT => 'في انتظار الدفع',
            self::STATUS_PROCESSING => 'قيد المعالجة',
            self::STATUS_DELIVERING => 'قيد التوصيل',
            self::STATUS_COMPLETED => 'مكتمل',
            self::STATUS_FAILED => 'فشل',
            self::STATUS_CANCELLED => 'ملغي',
            self::STATUS_REFUNDED => 'مسترد',
            self::STATUS_PARTIALLY_REFUNDED => 'مسترد جزئياً',
        ];
        
        return $labels[$this->status] ?? $this->status;
    }
    
    /**
     * Check if order can be cancelled
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING_PAYMENT, self::STATUS_PROCESSING]);
    }
    
    /**
     * Get delivered codes
     */
    public function getDeliveredCodes(): array
    {
        $items = $this->items();
        $codes = [];
        
        foreach ($items as $item) {
            if ($item['delivery_data']) {
                $itemCodes = json_decode($item['delivery_data'], true);
                if ($itemCodes) {
                    $codes[$item['product_name']] = $itemCodes;
                }
            }
        }
        
        return $codes;
    }
}
