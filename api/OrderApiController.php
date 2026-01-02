<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * ABOUD STORE - Order API Controller
 * ═══════════════════════════════════════════════════════════════════════════
 */

namespace Api;

use Controllers\BaseController;
use Core\Response;
use Models\Order;
use Models\Product;

if (!defined('ABOUD_STORE')) {
    die('Direct access not allowed');
}

class OrderApiController extends BaseController
{
    /**
     * GET /api/orders
     * Get user's orders
     */
    public function index(): void
    {
        $user = $this->app->container['api_user'];
        
        $page = (int) ($this->input('page') ?? 1);
        $perPage = min((int) ($this->input('per_page') ?? 15), 50);
        $status = $this->input('status');
        
        $query = Order::where('user_id', $user->id);
        
        if ($status) {
            $query->where('status', $status);
        }
        
        $result = $query->orderBy('created_at', 'DESC')->paginate($perPage, $page);
        
        Response::paginated(
            array_map(fn($o) => $o->toArray(), $result['data']),
            $result['total'],
            $result['page'],
            $result['per_page']
        );
    }
    
    /**
     * GET /api/orders/{uuid}
     * Get single order
     */
    public function show(array $params): void
    {
        $user = $this->app->container['api_user'];
        $uuid = $params['uuid'] ?? null;
        
        if (!$uuid) {
            Response::notFound('Order not found');
        }
        
        $order = Order::findByUuid($uuid);
        
        if (!$order || $order->user_id !== $user->id) {
            Response::notFound('Order not found');
        }
        
        $data = $order->toArray();
        $data['items'] = $order->items();
        $data['status_label'] = $order->getStatusLabel();
        $data['can_cancel'] = $order->canBeCancelled();
        
        // Include delivered codes if completed
        if ($order->status === Order::STATUS_COMPLETED) {
            $data['delivered_codes'] = $order->getDeliveredCodes();
        }
        
        $this->success($data);
    }
    
    /**
     * POST /api/orders
     * Create new order
     */
    public function store(): void
    {
        $user = $this->app->container['api_user'];
        
        $validator = $this->validate([
            'items' => 'required|array',
            'items.*.product_id' => 'required|integer',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.variant_id' => 'nullable|integer',
            'items.*.player_id' => 'nullable|string',
            'items.*.server' => 'nullable|string',
            'coupon_code' => 'nullable|string',
            'payment_method' => 'required|in:wallet,card,paypal'
        ]);
        
        $items = $validator->get('items');
        
        // Validate items
        foreach ($items as $index => $item) {
            $product = Product::find($item['product_id']);
            
            if (!$product || !$product->is_active) {
                $this->error("المنتج غير موجود", 'PRODUCT_NOT_FOUND', 400);
            }
            
            if (!$product->inStock()) {
                $this->error("المنتج {$product->name_ar} غير متوفر", 'OUT_OF_STOCK', 400);
            }
            
            if ($item['quantity'] < $product->min_quantity) {
                $this->error(
                    "الحد الأدنى للطلب هو {$product->min_quantity}",
                    'MIN_QUANTITY_ERROR',
                    400
                );
            }
            
            if ($item['quantity'] > $product->max_quantity) {
                $this->error(
                    "الحد الأقصى للطلب هو {$product->max_quantity}",
                    'MAX_QUANTITY_ERROR',
                    400
                );
            }
            
            // Check if player_id is required
            if ($product->requires_player_id && empty($item['player_id'])) {
                $this->error(
                    "يرجى إدخال {$product->player_id_label}",
                    'PLAYER_ID_REQUIRED',
                    400
                );
            }
            
            // Check if server is required
            if ($product->requires_server && empty($item['server'])) {
                $this->error('يرجى اختيار السيرفر', 'SERVER_REQUIRED', 400);
            }
        }
        
        try {
            $order = Order::createFromCart(
                $user->id,
                $items,
                $validator->get('coupon_code')
            );
            
            // Process payment
            $paymentMethod = $validator->get('payment_method');
            
            if ($paymentMethod === 'wallet') {
                $paymentResult = $order->payWithWallet();
                
                if (!$paymentResult) {
                    // Cancel order if payment fails
                    $order->cancel();
                    $this->error('رصيدك غير كافٍ', 'INSUFFICIENT_BALANCE', 400);
                }
            } else {
                // TODO: Handle other payment methods (card, paypal)
                $this->error('طريقة الدفع غير مدعومة حالياً', 'UNSUPPORTED_PAYMENT', 400);
            }
            
            // Log activity
            $user->logActivity('order.create', 'Order', $order->id, [
                'order_number' => $order->order_number,
                'total' => $order->total_amount
            ]);
            
            // Refresh order
            $order->refresh();
            
            $data = $order->toArray();
            $data['items'] = $order->items();
            $data['status_label'] = $order->getStatusLabel();
            
            // Include delivered codes if instant delivery
            if ($order->status === Order::STATUS_COMPLETED) {
                $data['delivered_codes'] = $order->getDeliveredCodes();
            }
            
            $this->success($data, 'تم إنشاء الطلب بنجاح');
            
        } catch (\Exception $e) {
            $this->error('فشل إنشاء الطلب: ' . $e->getMessage(), 'ORDER_FAILED', 500);
        }
    }
    
    /**
     * POST /api/orders/{uuid}/cancel
     * Cancel order
     */
    public function cancel(array $params): void
    {
        $user = $this->app->container['api_user'];
        $uuid = $params['uuid'] ?? null;
        
        if (!$uuid) {
            Response::notFound('Order not found');
        }
        
        $order = Order::findByUuid($uuid);
        
        if (!$order || $order->user_id !== $user->id) {
            Response::notFound('Order not found');
        }
        
        if (!$order->canBeCancelled()) {
            $this->error('لا يمكن إلغاء هذا الطلب', 'CANNOT_CANCEL', 400);
        }
        
        $result = $order->cancel();
        
        if (!$result) {
            $this->error('فشل إلغاء الطلب', 'CANCEL_FAILED', 500);
        }
        
        // Log activity
        $user->logActivity('order.cancel', 'Order', $order->id, [
            'order_number' => $order->order_number
        ]);
        
        $this->success(null, 'تم إلغاء الطلب بنجاح');
    }
}
