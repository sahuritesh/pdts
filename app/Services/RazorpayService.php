<?php

namespace App\Services;

use Razorpay\Api\Api;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class RazorpayService
{
    /**
     * Get active Razorpay mode from database
     * 
     * @return string Returns 'test' or 'production'
     */
    private function getActiveMode()
    {
        return Cache::remember('razorpay_active_mode', 3600, function() {
            $setting = DB::table('tbl_settings')
                ->where('type', 'PAYMENT_GATEWAY')
                ->where('title', 'razorpay_mode')
                ->first();
            return $setting->description ?? 'test';
        });
    }

    /**
     * Get Razorpay credentials based on active mode
     * 
     * @return array Returns ['key' => key, 'secret' => secret]
     */
    private function getCredentials()
    {
        $activeMode = $this->getActiveMode();
        
        if ($activeMode === 'production') {
            $razorpayKey = env('RAZORPAY_PRODUCTION_KEY', env('RAZORPAY_KEY', ''));
            $razorpaySecret = env('RAZORPAY_PRODUCTION_SECRET', env('RAZORPAY_SECRET', ''));
        } else {
            $razorpayKey = env('RAZORPAY_TEST_KEY', env('RAZORPAY_KEY', ''));
            $razorpaySecret = env('RAZORPAY_TEST_SECRET', env('RAZORPAY_SECRET', ''));
        }
        
        return [
            'key' => $razorpayKey,
            'secret' => $razorpaySecret
        ];
    }

    /**
     * Create Razorpay order
     * 
     * @param float $amount Amount in rupees
     * @param array $customerData Customer information (first_name, last_name, email_id, phone_number)
     * @param string $currency Currency code (default: INR)
     * @return array Returns ['id' => order_id, 'amount' => amount_in_paise] on success
     * @throws \Exception
     */
    public function createOrder($amount, $customerData = [], $currency = 'INR')
    {
        $credentials = $this->getCredentials();
        $razorpayKey = $credentials['key'];
        $razorpaySecret = $credentials['secret'];

        if (empty($razorpayKey) || empty($razorpaySecret)) {
            Log::error('Razorpay credentials not configured');
            throw new \Exception('Payment gateway is not configured. Please contact support.');
        }

        $api = new Api($razorpayKey, $razorpaySecret);
        $amountInPaise = (int)($amount * 100);

        // Format phone number for Razorpay (must be in international format)
        $contactNumber = $this->formatPhoneNumberForRazorpay($customerData['phone_number'] ?? '');
        
        $orderData = [
            'receipt' => 'MEM-' . time(),
            'amount' => $amountInPaise,
            'currency' => $currency,
            'notes' => [
                'email' => $customerData['email_id'] ?? '',
                'name' => trim(($customerData['first_name'] ?? '') . ' ' . ($customerData['last_name'] ?? '')),
                'phone' => $contactNumber,
            ]
        ];

        try {
            $razorpayOrder = $api->order->create($orderData);
            
            Log::info('Razorpay order created successfully', [
                'order_id' => $razorpayOrder['id'],
                'amount' => $amountInPaise,
                'email' => $customerData['email_id'] ?? ''
            ]);
            
            return [
                'id' => $razorpayOrder['id'],
                'amount' => $amountInPaise
            ];
        } catch (\Exception $e) {
            Log::error('Razorpay order creation failed', [
                'error' => $e->getMessage(),
                'amount' => $amountInPaise,
                'email' => $customerData['email_id'] ?? ''
            ]);
            throw $e;
        }
    }

    /**
     * Format phone number for Razorpay
     * Razorpay requires international format with country code (max 15 digits including country code)
     * 
     * @param string $phoneNumber Phone number in any format
     * @return string Formatted phone number in international format (e.g., +911234567890)
     */
    public function formatPhoneNumberForRazorpay($phoneNumber)
    {
        if (empty($phoneNumber)) {
            return '';
        }
        
        // Remove all non-numeric characters (spaces, dashes, parentheses, etc.)
        // But keep + if present at the start
        $cleaned = trim($phoneNumber);
        
        // Extract + if present
        $hasPlus = (strpos($cleaned, '+') === 0);
        if ($hasPlus) {
            $cleaned = substr($cleaned, 1); // Remove +
        }
        
        // Remove all non-numeric characters
        $digitsOnly = preg_replace('/[^0-9]/', '', $cleaned);
        
        // Remove leading zeros
        $digitsOnly = ltrim($digitsOnly, '0');
        
        // If empty after cleaning, return empty
        if (empty($digitsOnly)) {
            return '';
        }
        
        // Razorpay counts total digits (country code + phone number), not including +
        // For India: +91 (2 digits) + 10-digit number = 12 digits total (which is <= 15)
        
        // If 10 digits, assume India and add +91
        if (strlen($digitsOnly) == 10) {
            // Total: 2 (country code) + 10 (number) = 12 digits <= 15 ✓
            return '+91' . $digitsOnly;
        }
        
        // If 12 digits and starts with 91, it already has country code
        if (strlen($digitsOnly) == 12 && substr($digitsOnly, 0, 2) == '91') {
            // Total: 12 digits <= 15 ✓
            return '+' . $digitsOnly;
        }
        
        // If 11 digits
        if (strlen($digitsOnly) == 11) {
            // If starts with 0, remove leading 0 (common in Indian numbers)
            if (substr($digitsOnly, 0, 1) == '0') {
                $digitsOnly = substr($digitsOnly, 1); // Remove leading 0, now 10 digits
                return '+91' . $digitsOnly; // Total: 12 digits
            }
            // If starts with 91, it might be 91 + 9 digits (should be 10)
            // But we'll assume it's correct and add +
            if (substr($digitsOnly, 0, 2) == '91') {
                return '+' . $digitsOnly; // Total: 11 digits
            }
        }
        
        // If it's already in a valid international format (e.g., +12125551234)
        if ($hasPlus && strlen($digitsOnly) <= 15) {
            return '+' . $digitsOnly;
        }
        
        // Fallback: if it's a number that doesn't fit common patterns, try to force +91 and truncate
        if (strlen($digitsOnly) > 10) {
            // If longer than 10, take last 10 digits (assuming it's an Indian number with extra digits)
            $digitsOnly = substr($digitsOnly, -10); // Take last 10 digits
        }
        
        $formatted = '+91' . $digitsOnly;
        
        // If still too long, truncate to 15 digits (including country code)
        // +91 = 2 chars, so we need 13 digits max for the number part
        if (strlen($digitsOnly) > 13) {
            $digitsOnly = substr($digitsOnly, 0, 13); // Truncate to 13 digits
            $formatted = '+91' . $digitsOnly;
        }
        
        return $formatted;
    }

    /**
     * Verify Razorpay payment signature
     * 
     * @param string $razorpayOrderId Razorpay order ID
     * @param string $razorpayPaymentId Razorpay payment ID
     * @param string $razorpaySignature Razorpay signature
     * @return bool Returns true if signature is valid, false otherwise
     */
    public function verifyPaymentSignature($razorpayOrderId, $razorpayPaymentId, $razorpaySignature)
    {
        if (empty($razorpayPaymentId) || empty($razorpaySignature) || empty($razorpayOrderId)) {
            Log::error('Payment Verification: Missing payment data', [
                'order_id' => $razorpayOrderId,
                'payment_id' => $razorpayPaymentId
            ]);
            return false;
        }

        $credentials = $this->getCredentials();
        $razorpayKey = $credentials['key'];
        $razorpaySecret = $credentials['secret'];

        if (empty($razorpayKey) || empty($razorpaySecret)) {
            Log::error('Payment Verification: Razorpay credentials not configured');
            return false;
        }

        $api = new Api($razorpayKey, $razorpaySecret);

        try {
            $api->utility->verifyPaymentSignature([
                'razorpay_order_id' => $razorpayOrderId,
                'razorpay_payment_id' => $razorpayPaymentId,
                'razorpay_signature' => $razorpaySignature
            ]);
            
            Log::info('Payment signature verified successfully', [
                'order_id' => $razorpayOrderId,
                'payment_id' => $razorpayPaymentId
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Payment signature verification failed', [
                'error' => $e->getMessage(),
                'order_id' => $razorpayOrderId,
                'payment_id' => $razorpayPaymentId
            ]);
            return false;
        }
    }

    /**
     * Get Razorpay public key (for frontend/mobile initialization)
     * 
     * @return string
     */
    public function getPublicKey()
    {
        $credentials = $this->getCredentials();
        return $credentials['key'];
    }

    /**
     * Fetch complete payment details from Razorpay
     * 
     * @param string $razorpayPaymentId Razorpay payment ID
     * @return array|null Returns payment details array or null on failure
     */
    public function fetchPaymentDetails($razorpayPaymentId)
    {
        if (empty($razorpayPaymentId)) {
            Log::warning('Fetch Payment Details: Payment ID is empty');
            return null;
        }

        $credentials = $this->getCredentials();
        $razorpayKey = $credentials['key'];
        $razorpaySecret = $credentials['secret'];

        if (empty($razorpayKey) || empty($razorpaySecret)) {
            Log::error('Razorpay credentials not configured');
            return null;
        }

        try {
            $api = new Api($razorpayKey, $razorpaySecret);
            $payment = $api->payment->fetch($razorpayPaymentId);
            
            Log::info('Payment details fetched successfully', [
                'payment_id' => $razorpayPaymentId
            ]);
            
            // Convert payment object to array
            return $payment->toArray();
        } catch (\Exception $e) {
            Log::error('Failed to fetch payment details from Razorpay', [
                'payment_id' => $razorpayPaymentId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}

