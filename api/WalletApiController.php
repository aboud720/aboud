<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * ABOUD STORE - Wallet API Controller
 * ═══════════════════════════════════════════════════════════════════════════
 */

namespace Api;

use Controllers\BaseController;
use Core\Response;

if (!defined('ABOUD_STORE')) {
    die('Direct access not allowed');
}

class WalletApiController extends BaseController
{
    /**
     * GET /api/wallet
     * Get wallet balance
     */
    public function balance(): void
    {
        $user = $this->app->container['api_user'];
        
        $this->success([
            'balance' => $user->balance,
            'formatted_balance' => $user->getFormattedBalance(),
            'currency' => 'SAR'
        ]);
    }
    
    /**
     * GET /api/wallet/transactions
     * Get wallet transactions
     */
    public function transactions(): void
    {
        $user = $this->app->container['api_user'];
        
        $page = (int) ($this->input('page') ?? 1);
        $perPage = min((int) ($this->input('per_page') ?? 15), 50);
        $type = $this->input('type');
        
        $db = $this->app->db();
        
        $sql = "SELECT * FROM transactions WHERE user_id = ?";
        $params = [$user->id];
        
        if ($type) {
            $sql .= " AND type = ?";
            $params[] = $type;
        }
        
        // Get total
        $countResult = $db->selectOne(
            str_replace('SELECT *', 'SELECT COUNT(*) as count', $sql),
            $params
        );
        $total = (int) $countResult['count'];
        
        // Get paginated results
        $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $params[] = $perPage;
        $params[] = ($page - 1) * $perPage;
        
        $transactions = $db->select($sql, $params);
        
        // Format transactions
        $formatted = array_map(function($t) {
            return [
                'uuid' => $t['uuid'],
                'type' => $t['type'],
                'type_label' => $this->getTypeLabel($t['type']),
                'amount' => $t['amount'],
                'formatted_amount' => $this->formatAmount($t['amount']),
                'balance_after' => $t['balance_after'],
                'description' => $t['description'],
                'created_at' => $t['created_at']
            ];
        }, $transactions);
        
        Response::paginated($formatted, $total, $page, $perPage);
    }
    
    /**
     * GET /api/wallet/deposit-methods
     * Get available deposit methods
     */
    public function depositMethods(): void
    {
        $methods = [
            [
                'id' => 'card',
                'name' => 'بطاقة ائتمانية',
                'icon' => 'credit-card',
                'min' => 1000, // 10 SAR
                'max' => 500000, // 5000 SAR
                'fee' => 0
            ],
            [
                'id' => 'mada',
                'name' => 'مدى',
                'icon' => 'mada',
                'min' => 1000,
                'max' => 500000,
                'fee' => 0
            ],
            [
                'id' => 'stc_pay',
                'name' => 'STC Pay',
                'icon' => 'stc-pay',
                'min' => 1000,
                'max' => 200000,
                'fee' => 0
            ],
            [
                'id' => 'apple_pay',
                'name' => 'Apple Pay',
                'icon' => 'apple-pay',
                'min' => 1000,
                'max' => 500000,
                'fee' => 0
            ]
        ];
        
        $this->success($methods);
    }
    
    /**
     * POST /api/wallet/deposit
     * Initiate deposit
     */
    public function deposit(): void
    {
        $user = $this->app->container['api_user'];
        
        $validator = $this->validate([
            'amount' => 'required|integer|min:1000|max:500000',
            'method' => 'required|in:card,mada,stc_pay,apple_pay'
        ]);
        
        $amount = $validator->get('amount');
        $method = $validator->get('method');
        
        // TODO: Integrate with payment gateway
        // For now, we'll simulate a successful deposit
        
        // In production, this would:
        // 1. Create a pending transaction
        // 2. Initialize payment gateway
        // 3. Return payment URL/session
        
        $this->success([
            'payment_url' => 'https://payment.example.com/pay?session=xxx',
            'session_id' => 'ps_' . bin2hex(random_bytes(16)),
            'amount' => $amount,
            'method' => $method,
            'expires_at' => date('Y-m-d H:i:s', strtotime('+30 minutes'))
        ], 'تم إنشاء جلسة الدفع');
    }
    
    /**
     * POST /api/wallet/deposit/callback
     * Handle payment callback (webhook)
     */
    public function depositCallback(): void
    {
        // This would be called by the payment gateway
        // Verify webhook signature
        // Update transaction status
        // Add balance if successful
        
        $this->success(null, 'Callback processed');
    }
    
    /**
     * Get transaction type label
     */
    private function getTypeLabel(string $type): string
    {
        $labels = [
            'deposit' => 'إيداع',
            'withdrawal' => 'سحب',
            'purchase' => 'شراء',
            'refund' => 'استرداد',
            'bonus' => 'مكافأة',
            'transfer_in' => 'تحويل وارد',
            'transfer_out' => 'تحويل صادر',
            'commission' => 'عمولة'
        ];
        
        return $labels[$type] ?? $type;
    }
    
    /**
     * Format amount with sign
     */
    private function formatAmount(int $amount): string
    {
        $sign = $amount >= 0 ? '+' : '';
        return $sign . number_format($amount / 100, 2) . ' ر.س';
    }
}
