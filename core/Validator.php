<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * ABOUD STORE - Input Validator
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * @description  Comprehensive input validation
 * @security     Validates and sanitizes all user input
 * ═══════════════════════════════════════════════════════════════════════════
 */

namespace Core;

if (!defined('ABOUD_STORE')) {
    die('Direct access not allowed');
}

class Validator
{
    /**
     * Data to validate
     */
    private array $data = [];
    
    /**
     * Validation rules
     */
    private array $rules = [];
    
    /**
     * Custom error messages
     */
    private array $messages = [];
    
    /**
     * Validation errors
     */
    private array $errors = [];
    
    /**
     * Validated data
     */
    private array $validated = [];
    
    /**
     * Default error messages (Arabic)
     */
    private array $defaultMessages = [
        'required'     => 'حقل :field مطلوب',
        'string'       => 'حقل :field يجب أن يكون نصاً',
        'integer'      => 'حقل :field يجب أن يكون رقماً صحيحاً',
        'numeric'      => 'حقل :field يجب أن يكون رقماً',
        'email'        => 'حقل :field يجب أن يكون بريداً إلكترونياً صحيحاً',
        'min'          => 'حقل :field يجب أن يكون على الأقل :param حرفاً',
        'max'          => 'حقل :field يجب ألا يتجاوز :param حرفاً',
        'between'      => 'حقل :field يجب أن يكون بين :param1 و :param2',
        'confirmed'    => 'حقل :field غير متطابق',
        'unique'       => 'قيمة :field مستخدمة مسبقاً',
        'exists'       => 'قيمة :field غير موجودة',
        'in'           => 'قيمة :field غير صالحة',
        'regex'        => 'صيغة :field غير صحيحة',
        'date'         => 'حقل :field يجب أن يكون تاريخاً صحيحاً',
        'url'          => 'حقل :field يجب أن يكون رابطاً صحيحاً',
        'phone'        => 'حقل :field يجب أن يكون رقم هاتف صحيحاً',
        'username'     => 'حقل :field يجب أن يكون 3-30 حرفاً (أحرف وأرقام و _ فقط)',
        'password'     => 'كلمة المرور يجب أن تحتوي على 8 أحرف على الأقل',
        'array'        => 'حقل :field يجب أن يكون مصفوفة',
        'file'         => 'حقل :field يجب أن يكون ملفاً',
        'image'        => 'حقل :field يجب أن يكون صورة',
        'mimes'        => 'نوع الملف :field غير مسموح',
        'max_size'     => 'حجم الملف :field كبير جداً',
    ];
    
    /**
     * Create new validator
     */
    public static function make(array $data, array $rules, array $messages = []): self
    {
        $validator = new self();
        $validator->data = $data;
        $validator->rules = $rules;
        $validator->messages = $messages;
        
        return $validator;
    }
    
    /**
     * Run validation
     */
    public function validate(): bool
    {
        $this->errors = [];
        $this->validated = [];
        
        foreach ($this->rules as $field => $rules) {
            $this->validateField($field, $rules);
        }
        
        return empty($this->errors);
    }
    
    /**
     * Validate single field
     */
    private function validateField(string $field, $rules): void
    {
        // Convert string rules to array
        if (is_string($rules)) {
            $rules = explode('|', $rules);
        }
        
        $value = $this->getValue($field);
        
        // Check if field is nullable
        $isNullable = in_array('nullable', $rules);
        if ($isNullable && ($value === null || $value === '')) {
            return;
        }
        
        foreach ($rules as $rule) {
            if ($rule === 'nullable') {
                continue;
            }
            
            // Parse rule name and parameters
            $params = [];
            if (strpos($rule, ':') !== false) {
                list($rule, $paramString) = explode(':', $rule, 2);
                $params = explode(',', $paramString);
            }
            
            // Run validation rule
            $method = 'validate' . ucfirst($rule);
            if (method_exists($this, $method)) {
                if (!$this->$method($field, $value, $params)) {
                    break; // Stop on first error
                }
            }
        }
        
        // Add to validated data if no errors
        if (!isset($this->errors[$field])) {
            $this->validated[$field] = $value;
        }
    }
    
    /**
     * Get value from data (supports dot notation)
     */
    private function getValue(string $field)
    {
        $keys = explode('.', $field);
        $value = $this->data;
        
        foreach ($keys as $key) {
            if (!isset($value[$key])) {
                return null;
            }
            $value = $value[$key];
        }
        
        return $value;
    }
    
    /**
     * Add error
     */
    private function addError(string $field, string $rule, array $params = []): void
    {
        // Get custom message or default
        $message = $this->messages[$field . '.' . $rule]
            ?? $this->messages[$field]
            ?? $this->defaultMessages[$rule]
            ?? 'حقل :field غير صالح';
        
        // Replace placeholders
        $message = str_replace(':field', $field, $message);
        
        foreach ($params as $i => $param) {
            $message = str_replace(':param' . ($i + 1), $param, $message);
            $message = str_replace(':param', $param, $message);
        }
        
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        
        $this->errors[$field][] = $message;
    }
    
    // ═══════════════════════════════════════════════════════════════════════
    // VALIDATION RULES
    // ═══════════════════════════════════════════════════════════════════════
    
    private function validateRequired(string $field, $value): bool
    {
        $valid = $value !== null && $value !== '' && $value !== [];
        if (!$valid) {
            $this->addError($field, 'required');
        }
        return $valid;
    }
    
    private function validateString(string $field, $value): bool
    {
        if ($value === null) return true;
        $valid = is_string($value);
        if (!$valid) {
            $this->addError($field, 'string');
        }
        return $valid;
    }
    
    private function validateInteger(string $field, $value): bool
    {
        if ($value === null) return true;
        $valid = filter_var($value, FILTER_VALIDATE_INT) !== false;
        if (!$valid) {
            $this->addError($field, 'integer');
        }
        return $valid;
    }
    
    private function validateNumeric(string $field, $value): bool
    {
        if ($value === null) return true;
        $valid = is_numeric($value);
        if (!$valid) {
            $this->addError($field, 'numeric');
        }
        return $valid;
    }
    
    private function validateEmail(string $field, $value): bool
    {
        if ($value === null) return true;
        $valid = filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
        if (!$valid) {
            $this->addError($field, 'email');
        }
        return $valid;
    }
    
    private function validateMin(string $field, $value, array $params): bool
    {
        if ($value === null) return true;
        $min = (int) $params[0];
        
        $length = is_string($value) ? mb_strlen($value) : (is_array($value) ? count($value) : $value);
        $valid = $length >= $min;
        
        if (!$valid) {
            $this->addError($field, 'min', $params);
        }
        return $valid;
    }
    
    private function validateMax(string $field, $value, array $params): bool
    {
        if ($value === null) return true;
        $max = (int) $params[0];
        
        $length = is_string($value) ? mb_strlen($value) : (is_array($value) ? count($value) : $value);
        $valid = $length <= $max;
        
        if (!$valid) {
            $this->addError($field, 'max', $params);
        }
        return $valid;
    }
    
    private function validateBetween(string $field, $value, array $params): bool
    {
        if ($value === null) return true;
        $min = (int) $params[0];
        $max = (int) $params[1];
        
        $length = is_string($value) ? mb_strlen($value) : $value;
        $valid = $length >= $min && $length <= $max;
        
        if (!$valid) {
            $this->addError($field, 'between', $params);
        }
        return $valid;
    }
    
    private function validateConfirmed(string $field, $value): bool
    {
        if ($value === null) return true;
        $confirmation = $this->getValue($field . '_confirmation');
        $valid = $value === $confirmation;
        
        if (!$valid) {
            $this->addError($field, 'confirmed');
        }
        return $valid;
    }
    
    private function validateUnique(string $field, $value, array $params): bool
    {
        if ($value === null) return true;
        
        $table = $params[0];
        $column = $params[1] ?? $field;
        $exceptId = $params[2] ?? null;
        
        $db = Application::getInstance()->db();
        
        $sql = "SELECT COUNT(*) as count FROM `{$table}` WHERE `{$column}` = ?";
        $bindings = [$value];
        
        if ($exceptId) {
            $sql .= " AND id != ?";
            $bindings[] = $exceptId;
        }
        
        $result = $db->selectOne($sql, $bindings);
        $valid = (int) $result['count'] === 0;
        
        if (!$valid) {
            $this->addError($field, 'unique');
        }
        return $valid;
    }
    
    private function validateExists(string $field, $value, array $params): bool
    {
        if ($value === null) return true;
        
        $table = $params[0];
        $column = $params[1] ?? $field;
        
        $db = Application::getInstance()->db();
        
        $result = $db->selectOne(
            "SELECT COUNT(*) as count FROM `{$table}` WHERE `{$column}` = ?",
            [$value]
        );
        
        $valid = (int) $result['count'] > 0;
        
        if (!$valid) {
            $this->addError($field, 'exists');
        }
        return $valid;
    }
    
    private function validateIn(string $field, $value, array $params): bool
    {
        if ($value === null) return true;
        $valid = in_array($value, $params, true);
        
        if (!$valid) {
            $this->addError($field, 'in');
        }
        return $valid;
    }
    
    private function validateRegex(string $field, $value, array $params): bool
    {
        if ($value === null) return true;
        $pattern = $params[0];
        $valid = (bool) preg_match($pattern, $value);
        
        if (!$valid) {
            $this->addError($field, 'regex');
        }
        return $valid;
    }
    
    private function validateDate(string $field, $value): bool
    {
        if ($value === null) return true;
        $valid = strtotime($value) !== false;
        
        if (!$valid) {
            $this->addError($field, 'date');
        }
        return $valid;
    }
    
    private function validateUrl(string $field, $value): bool
    {
        if ($value === null) return true;
        $valid = filter_var($value, FILTER_VALIDATE_URL) !== false;
        
        if (!$valid) {
            $this->addError($field, 'url');
        }
        return $valid;
    }
    
    private function validatePhone(string $field, $value): bool
    {
        if ($value === null) return true;
        $valid = Application::getInstance()->security()->validatePhone($value);
        
        if (!$valid) {
            $this->addError($field, 'phone');
        }
        return $valid;
    }
    
    private function validateUsername(string $field, $value): bool
    {
        if ($value === null) return true;
        $valid = Application::getInstance()->security()->validateUsername($value);
        
        if (!$valid) {
            $this->addError($field, 'username');
        }
        return $valid;
    }
    
    private function validatePassword(string $field, $value): bool
    {
        if ($value === null) return true;
        $errors = Application::getInstance()->security()->validatePasswordStrength($value);
        
        if (!empty($errors)) {
            foreach ($errors as $error) {
                if (!isset($this->errors[$field])) {
                    $this->errors[$field] = [];
                }
                $this->errors[$field][] = $error;
            }
            return false;
        }
        return true;
    }
    
    private function validateArray(string $field, $value): bool
    {
        if ($value === null) return true;
        $valid = is_array($value);
        
        if (!$valid) {
            $this->addError($field, 'array');
        }
        return $valid;
    }
    
    // ═══════════════════════════════════════════════════════════════════════
    // GETTERS
    // ═══════════════════════════════════════════════════════════════════════
    
    /**
     * Check if validation failed
     */
    public function fails(): bool
    {
        return !empty($this->errors);
    }
    
    /**
     * Get all errors
     */
    public function errors(): array
    {
        return $this->errors;
    }
    
    /**
     * Get first error for field
     */
    public function firstError(string $field): ?string
    {
        return $this->errors[$field][0] ?? null;
    }
    
    /**
     * Get all first errors
     */
    public function firstErrors(): array
    {
        $result = [];
        foreach ($this->errors as $field => $errors) {
            $result[$field] = $errors[0];
        }
        return $result;
    }
    
    /**
     * Get validated data
     */
    public function validated(): array
    {
        return $this->validated;
    }
    
    /**
     * Get specific validated value
     */
    public function get(string $field, $default = null)
    {
        return $this->validated[$field] ?? $default;
    }
}
