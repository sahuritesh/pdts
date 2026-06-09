<?php

namespace App\Http\Traits;

use App\Models\Common_model;
use App\Models\Email_Model;
use Illuminate\Support\Facades\Log;

trait EmailTrait
{
    /**
     * Send email using template from tbl_emailtemplates
     * 
     * @param string|int $templateIdentifier Template ID (int) or Template Name (string)
     * @param string $recipientEmail Recipient email address
     * @param array $replacements Array of placeholder replacements (e.g., ['##NAME##' => 'John Doe'])
     * @param array $options Additional options (cc, attachments, etc.)
     * @return bool True if email sent successfully, false otherwise
     */
    protected function sendEmailFromTemplate($templateIdentifier, $recipientEmail, $replacements = [], $options = [])
    {
        try {
            // Determine if identifier is ID or name
            $whereCondition = is_numeric($templateIdentifier) 
                ? ['id' => $templateIdentifier]
                : ['template_name' => $templateIdentifier];

            // Get email template
            $emailTemplate = Common_model::getDataFromTable(
                'tbl_emailtemplates',
                ['template_subject', 'template_body', 'template_otheremails', 'status'],
                $whereCondition,
                '',
                '',
                'ASC',
                '',
                0,
                true,
                ''
            );

            if (empty($emailTemplate) || !isset($emailTemplate[0])) {
                Log::warning('Email template not found', [
                    'identifier' => $templateIdentifier,
                    'recipient' => $recipientEmail
                ]);
                return false;
            }

            $template = $emailTemplate[0];

            // Check if template is active
            if (isset($template['status']) && $template['status'] != ACTIVE) {
                Log::warning('Email template is not active', [
                    'identifier' => $templateIdentifier,
                    'status' => $template['status']
                ]);
                return false;
            }

            $subject = $template['template_subject'] ?? '';
            $emailBody = $template['template_body'] ?? '';

            // Default replacements (can be overridden by passed replacements)
            $defaultReplacements = [
                '##SITENAME##' => env('APP_NAME', ''),
                '##SITEURL##' => env('APP_URL', ''),
                '##LOGINURL##' => env('APP_URL', ''),
            ];

            // Merge default replacements with custom replacements (custom takes precedence)
            $allReplacements = array_merge($defaultReplacements, $replacements);

            // Replace placeholders in email body
            foreach ($allReplacements as $key => $value) {
                $emailBody = str_replace($key, $value ?? '', $emailBody);
            }

            // Replace placeholders in subject as well
            foreach ($allReplacements as $key => $value) {
                $subject = str_replace($key, $value ?? '', $subject);
            }

            // Prepare email data
            $emailData = ['email_body' => $emailBody];
            $view = 'layouts.emailtemplate';
            $cc = $options['cc'] ?? $template['template_otheremails'] ?? '';
            $attachment = $options['attachment'] ?? null;

            // Send email
            $result = Email_Model::send_email($recipientEmail, $subject, $view, $emailData, $cc, $attachment);

            if ($result) {
                Log::info('Email sent successfully', [
                    'template' => $templateIdentifier,
                    'recipient' => $recipientEmail
                ]);
            } else {
                Log::warning('Email sending failed', [
                    'template' => $templateIdentifier,
                    'recipient' => $recipientEmail
                ]);
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('Email sending error: ' . $e->getMessage(), [
                'template' => $templateIdentifier,
                'recipient' => $recipientEmail,
                'error' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Send welcome registration email
     * Convenience method specifically for membership/conference registrations
     * 
     * @param string $recipientEmail Recipient email address
     * @param array $userData User data (first_name, last_name, email_id, password, serial_number, etc.)
     * @param int|null $templateId Template ID (default: uses EMAIL_TEMPLATE_WELCOME constant)
     * @return bool
     */
    protected function sendWelcomeRegistrationEmail($recipientEmail, $userData, $templateId = null)
    {
        // Use constant if template ID not provided
        if ($templateId === null) {
            $templateId = EMAIL_TEMPLATE_WELCOME;
        }

        $replacements = [
            '##NAME##' => ucfirst($userData['first_name'] ?? ''),
            '##FIRST_NAME##' => ucfirst($userData['first_name'] ?? ''),
            '##LAST_NAME##' => ucfirst($userData['last_name'] ?? ''),
            '##FULL_NAME##' => ucfirst(($userData['first_name'] ?? '') . ' ' . ($userData['last_name'] ?? '')),
            '##USERNAME##' => $userData['email_id'] ?? $recipientEmail,
            '##EMAIL##' => $userData['email_id'] ?? $recipientEmail,
            '##PASSWORD##' => $userData['password'] ?? '',
            '##SERIAL_NUMBER##' => $userData['serial_number'] ?? '',
            '##REGISTRATION_NUMBER##' => $userData['serial_number'] ?? '',
            '##MOBILE##' => $userData['mobile_no'] ?? $userData['phone_number'] ?? '',
            '##PHONE##' => $userData['mobile_no'] ?? $userData['phone_number'] ?? '',
        ];       

        return $this->sendEmailFromTemplate($templateId, $recipientEmail, $replacements);
    }

    /**
     * Send forgot password OTP email
     * Generic method that can be used by both web and mobile
     * 
     * @param array $userData User data (must include: email_id, first_name, last_name)
     * @param string $otpCode OTP code (6 digits)
     * @param int|null $otpExpiryMinutes OTP expiry in minutes (default: uses OTP_EXPIRY_MINUTES constant)
     * @param int|null $templateId Template ID (default: uses EMAIL_TEMPLATE_FORGOT_PASSWORD constant)
     * @return bool True if email sent successfully, false otherwise
     */
    protected function sendForgotPasswordOtpEmail($userData, $otpCode, $otpExpiryMinutes = null, $templateId = null)
    {
        try {
            // Use constant if template ID not provided
            if ($templateId === null) {
                $templateId = EMAIL_TEMPLATE_FORGOT_PASSWORD;
            }

            // Use constant if OTP expiry not provided
            if ($otpExpiryMinutes === null) {
                $otpExpiryMinutes = OTP_EXPIRY_MINUTES;
            }

            $recipientEmail = $userData['email_id'] ?? null;
            if (empty($recipientEmail)) {
                Log::warning('Forgot Password OTP: Recipient email is empty');
                return false;
            }

            if (empty($otpCode)) {
                Log::warning('Forgot Password OTP: OTP code is empty');
                return false;
            }

            $replacements = [
                '##NAME##' => ucfirst($userData['first_name'] ?? ''),
                '##FIRST_NAME##' => ucfirst($userData['first_name'] ?? ''),
                '##LAST_NAME##' => ucfirst($userData['last_name'] ?? ''),
                '##FULL_NAME##' => ucfirst(($userData['first_name'] ?? '') . ' ' . ($userData['last_name'] ?? '')),
                '##OTP_CODE##' => $otpCode,
                '##OTP##' => $otpCode,
                '##OTP_EXPIRY##' => (string)$otpExpiryMinutes,
                '##USERNAME##' => $userData['email_id'] ?? $recipientEmail,
                '##EMAIL##' => $userData['email_id'] ?? $recipientEmail,
                '##RESETURL##' => url('resetpassword'),
            ];

            return $this->sendEmailFromTemplate($templateId, $recipientEmail, $replacements);
        } catch (\Exception $e) {
            Log::error('Send Forgot Password OTP Email Error: ' . $e->getMessage(), [
                'user_email' => $userData['email_id'] ?? 'N/A',
                'error' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Send password change notification to user
     * Generic method that can be used by both web and mobile
     * 
     * @param array $userData User data (must include: email_id, first_name)
     * @param int|null $templateId Template ID (default: uses EMAIL_TEMPLATE_PASSWORD_CHANGE constant)
     * @return bool True if email sent successfully, false otherwise
     */
    protected function sendPasswordChangeNotification($userData, $templateId = null)
    {
        try {
            // Use constant if template ID not provided
            if ($templateId === null) {
                $templateId = EMAIL_TEMPLATE_PASSWORD_CHANGE;
            }

            $recipientEmail = $userData['email_id'] ?? null;
            if (empty($recipientEmail)) {
                Log::warning('Password Change Notification: Recipient email is empty');
                return false;
            }

            $replacements = [
                '##NAME##' => ucfirst($userData['first_name'] ?? ''),
                '##FIRST_NAME##' => ucfirst($userData['first_name'] ?? ''),
                '##LAST_NAME##' => ucfirst($userData['last_name'] ?? ''),
                '##FULL_NAME##' => ucfirst(($userData['first_name'] ?? '') . ' ' . ($userData['last_name'] ?? '')),
                '##USERNAME##' => $userData['email_id'] ?? $recipientEmail,
                '##EMAIL##' => $userData['email_id'] ?? $recipientEmail,
                '##CHANGE_DATE##' => date('d-m-Y H:i:s'),
                '##CHANGE_TIME##' => date('H:i:s'),
                '##CHANGE_DATETIME##' => current_datetime(),
                '##PASSWORD##' => $userData['new_password']
            ];

            return $this->sendEmailFromTemplate($templateId, $recipientEmail, $replacements);
        } catch (\Exception $e) {
            Log::error('Send Password Change Notification Error: ' . $e->getMessage(), [
                'user_email' => $userData['email_id'] ?? 'N/A',
                'error' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Send new user onboarded notification to admin
     * Generic method that can be used by both web and mobile
     * 
     * @param array $userData User data (must include: email_id, first_name, last_name, user_type, etc.)
     * @param int|null $templateId Template ID (default: uses EMAIL_TEMPLATE_NEW_USER_ONBOARDED constant)
     * @param string|array $adminEmails Admin email(s) to notify (if not provided, fetches from database)
     * @param int|null $registrationId Registration ID to fetch payment transaction details
     * @return bool True if email sent successfully, false otherwise
     */
    protected function sendNewUserOnboardedNotification($userData, $templateId = null, $adminEmails = null, $registrationId = null)
    {
        try {
            // Use constant if template ID not provided
            if ($templateId === null) {
                $templateId = EMAIL_TEMPLATE_NEW_USER_ONBOARDED;
            }

            // Get admin emails - priority: passed parameter > constant > template_otheremails
            $recipientEmails = $adminEmails;
            
            if (empty($recipientEmails)) {
                // Use constant from config/constants.php (comma-separated list)
                $recipientEmails = ADMIN_NOTIFICATION_EMAILS;
                
                // Convert to array if it's a string
                if (!empty($recipientEmails) && is_string($recipientEmails)) {
                    $recipientEmails = explode(',', $recipientEmails);
                    $recipientEmails = array_map('trim', $recipientEmails);
                    $recipientEmails = array_filter($recipientEmails); // Remove empty values
                    $recipientEmails = array_values($recipientEmails); // Re-index array
                }
            }
            
            // Fallback to template_otheremails if still empty
            if (empty($recipientEmails)) {
                $emailTemplate = Common_model::getDataFromTable(
                    'tbl_emailtemplates',
                    ['template_otheremails'],
                    ['id' => $templateId],
                    '',
                    '',
                    'ASC',
                    '',
                    0,
                    true,
                    ''
                );

                if (!empty($emailTemplate) && isset($emailTemplate[0]['template_otheremails'])) {
                    $recipientEmails = $emailTemplate[0]['template_otheremails'];
                }
            }

            if (empty($recipientEmails)) {
                Log::warning('New User Onboarded Notification: No admin email configured');
                return false;
            }

            // Handle multiple emails (comma-separated or array)
            $emailList = is_array($recipientEmails) ? $recipientEmails : explode(',', $recipientEmails);
            $emailList = array_map('trim', $emailList);
            $emailList = array_filter($emailList); // Remove empty values

            if (empty($emailList)) {
                Log::warning('New User Onboarded Notification: No valid admin emails found');
                return false;
            }

            // Get role name if available - using tbl_roles instead of tbl_user_types
            $roleName = $userData['role_name'] ?? '';
            if (empty($roleName) && !empty($userData['user_type'])) {
                // Try to get role name from tbl_roles
                $role = Common_model::getDataFromTable(
                    'tbl_roles',
                    ['role_name'],
                    ['id' => $userData['user_type']],
                    '',
                    '',
                    'ASC',
                    '',
                    0,
                    true,
                    ''
                );
                $roleName = !empty($role) && isset($role[0]['role_name']) 
                    ? $role[0]['role_name'] 
                    : 'User';
            }            
                 
            $planName = $userData['plan_name'] ?? 'Membership Plan'; // Default if not provided
            $currency = $userData['currency'] ?? 'INR';            
         

            $fullName = ucfirst(trim(($userData['first_name'] ?? '') . ' ' . ($userData['last_name'] ?? '')));

            $replacements = [
                '##USERNAME##' => $fullName, // Template uses ##USERNAME##
                '##USER_NAME##' => $fullName,
                '##FIRST_NAME##' => ucfirst($userData['first_name'] ?? ''),
                '##LAST_NAME##' => ucfirst($userData['last_name'] ?? ''),
                '##FULL_NAME##' => $fullName,
                '##USER_EMAIL##' => $userData['email_id'] ?? '',
                '##EMAIL##' => $userData['email_id'] ?? '',
                '##USER_TYPE##' => $roleName,
                '##ROLE_NAME##' => $roleName,
                '##PHONE##' => $userData['phone_number'] ?? $userData['mobile_no'] ?? '',
                '##MOBILE##' => $userData['phone_number'] ?? $userData['mobile_no'] ?? '',
                '##PLANNAME##' => $planName,
                '##AMOUNT##' => $userData['amount'].''.$userData['currency'],
                '##SERIALNUMBER##' => $userData['serial_number'],
                '##CREATED_DATE##' => date('d-m-Y H:i:s'),
                '##CREATED_TIME##' => date('H:i:s'),
                '##CREATED_DATETIME##' => current_datetime(),
            ];

            // Validate and filter email addresses
            $validEmails = [];
            foreach ($emailList as $adminEmail) {
                if (!empty($adminEmail) && filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
                    $validEmails[] = trim($adminEmail);
                }
            }

            if (empty($validEmails)) {
                Log::warning('New User Onboarded Notification: No valid admin emails found');
                return false;
            }

            // Send single email with all admins in CC for better performance
            // Use first admin as primary recipient, rest in CC
            $primaryRecipient = $validEmails[0];
            $ccRecipients = count($validEmails) > 1 ? array_slice($validEmails, 1) : [];

            $options = [];
            if (!empty($ccRecipients)) {
                // Convert CC array to comma-separated string or keep as array (Email_Model handles both)
                $options['cc'] = implode(',', $ccRecipients);
            }

            $result = $this->sendEmailFromTemplate($templateId, $primaryRecipient, $replacements, $options);

            if ($result) {
                Log::info('New User Onboarded Notification sent', [
                    'template' => $templateId,
                    'primary_recipient' => $primaryRecipient,
                    'cc_recipients' => count($ccRecipients),
                    'total_admins' => count($validEmails),
                    'new_user_email' => $userData['email_id'] ?? 'N/A',
                    'registration_id' => $registrationId ?? 'N/A'
                ]);
                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Send New User Onboarded Notification Error: ' . $e->getMessage(), [
                'user_email' => $userData['email_id'] ?? 'N/A',
                'registration_id' => $registrationId ?? 'N/A',
                'error' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Send payment failure notification to admin
     * Generic method that works for both membership and conference registrations
     * 
     * @param array $paymentData Payment/transaction data (must include: registration_type, amount, currency, etc.)
     * @param array $userData User data (first_name, last_name OR full_name, email_id OR email, phone_number OR mobile)
     * @param int|null $templateId Template ID (default: uses EMAIL_TEMPLATE_PAYMENT_FAILURE_ADMIN constant)
     * @param string|array $adminEmails Admin email(s) to notify (if not provided, fetches from database)
     * @return bool True if email sent successfully, false otherwise
     */
    protected function sendPaymentFailureAdminNotification($paymentData, $userData = [], $templateId = null, $adminEmails = null)
    {
        try {
            // Use constant if template ID not provided
            if ($templateId === null) {
                $templateId = EMAIL_TEMPLATE_PAYMENT_FAILURE_ADMIN;
            }

            // Get admin emails - priority: passed parameter > constant > template_otheremails
            $recipientEmails = $adminEmails;
            
            if (empty($recipientEmails)) {
                // Use constant from config/constants.php (comma-separated list)
                $recipientEmails = ADMIN_NOTIFICATION_EMAILS;
                
                // Convert to array if it's a string
                if (!empty($recipientEmails) && is_string($recipientEmails)) {
                    $recipientEmails = explode(',', $recipientEmails);
                    $recipientEmails = array_map('trim', $recipientEmails);
                    $recipientEmails = array_filter($recipientEmails); // Remove empty values
                    $recipientEmails = array_values($recipientEmails); // Re-index array
                }
            }
            
            // Fallback to template_otheremails if still empty
            if (empty($recipientEmails)) {
                $emailTemplate = Common_model::getDataFromTable(
                    'tbl_emailtemplates',
                    ['template_otheremails'],
                    ['id' => $templateId],
                    '',
                    '',
                    'ASC',
                    '',
                    0,
                    true,
                    ''
                );

                if (!empty($emailTemplate) && isset($emailTemplate[0]['template_otheremails'])) {
                    $recipientEmails = $emailTemplate[0]['template_otheremails'];
                }
            }

            if (empty($recipientEmails)) {
                Log::warning('Payment Failure Admin Notification: No admin email configured');
                return false;
            }

            // Handle multiple emails (comma-separated or array)
            $emailList = is_array($recipientEmails) ? $recipientEmails : explode(',', $recipientEmails);
            $emailList = array_map('trim', $emailList);
            $emailList = array_filter($emailList); // Remove empty values

            if (empty($emailList)) {
                Log::warning('Payment Failure Admin Notification: No valid admin emails found');
                return false;
            }

            // Extract user data - handle both membership and conference formats
            $firstName = $userData['first_name'] ?? '';
            $lastName = $userData['last_name'] ?? '';
            $fullName = $userData['full_name'] ?? '';
            
            // If full_name is provided but first_name/last_name are not, use full_name
            if (empty($firstName) && empty($lastName) && !empty($fullName)) {
                $nameParts = explode(' ', $fullName, 2);
                $firstName = $nameParts[0] ?? '';
                $lastName = $nameParts[1] ?? '';
            }
            
            // If we have first_name/last_name but no full_name, construct it
            if (empty($fullName) && (!empty($firstName) || !empty($lastName))) {
                $fullName = trim($firstName . ' ' . $lastName);
            }

            $email = $userData['email_id'] ?? $userData['email'] ?? '';
            $phone = $userData['phone_number'] ?? $userData['mobile'] ?? $userData['mobile_no'] ?? '';
            
            // Extract payment data
            $registrationType = ucfirst($paymentData['registration_type'] ?? 'Registration');
            $amount = $paymentData['amount'] ?? 0;
            $currency = $paymentData['currency'] ?? 'INR';
            $orderId = $paymentData['gateway_order_id'] ?? $paymentData['order_id'] ?? 'N/A';
            $paymentId = $paymentData['gateway_transaction_id'] ?? $paymentData['payment_id'] ?? 'N/A';
            $transactionId = $paymentData['transaction_id'] ?? 'N/A';
            $failureReason = $paymentData['failure_reason'] ?? 'Payment failed';
            $failedDate = $paymentData['payment_date'] ?? $paymentData['created_on'] ?? date('Y-m-d H:i:s');

            $replacements = [
                '##REGISTRATION_TYPE##' => $registrationType,
                '##FULL_NAME##' => $fullName,
                '##FIRST_NAME##' => ucfirst($firstName),
                '##LAST_NAME##' => ucfirst($lastName),
                '##EMAIL##' => $email,
                '##PHONE##' => $phone,
                '##MOBILE##' => $phone,
                '##AMOUNT##' => number_format((float)$amount, 2),
                '##CURRENCY##' => $currency,
                '##ORDER_ID##' => $orderId,
                '##PAYMENT_ID##' => $paymentId,
                '##TRANSACTION_ID##' => $transactionId,
                '##FAILURE_REASON##' => $failureReason,
                '##FAILED_DATE##' => date('d-m-Y H:i:s', strtotime($failedDate)),
            ];

            // Validate and filter email addresses
            $validEmails = [];
            foreach ($emailList as $adminEmail) {
                if (!empty($adminEmail) && filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
                    $validEmails[] = trim($adminEmail);
                }
            }

            if (empty($validEmails)) {
                Log::warning('Payment Failure Admin Notification: No valid admin emails found');
                return false;
            }

            // Send single email with all admins in CC for better performance
            $primaryRecipient = $validEmails[0];
            $ccRecipients = count($validEmails) > 1 ? array_slice($validEmails, 1) : [];

            $options = [];
            if (!empty($ccRecipients)) {
                $options['cc'] = implode(',', $ccRecipients);
            }

            $result = $this->sendEmailFromTemplate($templateId, $primaryRecipient, $replacements, $options);

            if ($result) {
                Log::info('Payment Failure Admin Notification sent', [
                    'template' => $templateId,
                    'primary_recipient' => $primaryRecipient,
                    'cc_recipients' => count($ccRecipients),
                    'registration_type' => $registrationType,
                    'user_email' => $email,
                    'transaction_id' => $transactionId
                ]);
                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Send Payment Failure Admin Notification Error: ' . $e->getMessage(), [
                'registration_type' => $paymentData['registration_type'] ?? 'N/A',
                'user_email' => $userData['email_id'] ?? $userData['email'] ?? 'N/A',
                'error' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Send registration failure after payment success notification to admin (Critical - Refund Required)
     * Generic method that works for both membership and conference registrations
     * 
     * @param array $paymentData Payment/transaction data (must include: registration_type, amount, currency, payment_id, etc.)
     * @param array $userData User data (first_name, last_name OR full_name, email_id OR email, phone_number OR mobile)
     * @param string $failureReason Detailed failure reason
     * @param int|null $templateId Template ID (default: uses EMAIL_TEMPLATE_PAYMENT_SUCCESS_REGISTRATION_FAILURE_ADMIN constant)
     * @param string|array $adminEmails Admin email(s) to notify (if not provided, fetches from database)
     * @return bool True if email sent successfully, false otherwise
     */
    protected function sendRegistrationFailureAfterPaymentAdminNotification($paymentData, $userData = [], $failureReason = '', $templateId = null, $adminEmails = null)
    {
        try {
            // Use constant if template ID not provided
            if ($templateId === null) {
                $templateId = EMAIL_TEMPLATE_PAYMENT_SUCCESS_REGISTRATION_FAILURE_ADMIN;
            }

            // Get admin emails - priority: passed parameter > constant > template_otheremails
            $recipientEmails = $adminEmails;
            
            if (empty($recipientEmails)) {
                // Use constant from config/constants.php (comma-separated list)
                $recipientEmails = ADMIN_NOTIFICATION_EMAILS;
                
                // Convert to array if it's a string
                if (!empty($recipientEmails) && is_string($recipientEmails)) {
                    $recipientEmails = explode(',', $recipientEmails);
                    $recipientEmails = array_map('trim', $recipientEmails);
                    $recipientEmails = array_filter($recipientEmails); // Remove empty values
                    $recipientEmails = array_values($recipientEmails); // Re-index array
                }
            }
            
            // Fallback to template_otheremails if still empty
            if (empty($recipientEmails)) {
                $emailTemplate = Common_model::getDataFromTable(
                    'tbl_emailtemplates',
                    ['template_otheremails'],
                    ['id' => $templateId],
                    '',
                    '',
                    'ASC',
                    '',
                    0,
                    true,
                    ''
                );

                if (!empty($emailTemplate) && isset($emailTemplate[0]['template_otheremails'])) {
                    $recipientEmails = $emailTemplate[0]['template_otheremails'];
                }
            }

            if (empty($recipientEmails)) {
                Log::warning('Registration Failure After Payment Admin Notification: No admin email configured');
                return false;
            }

            // Handle multiple emails (comma-separated or array)
            $emailList = is_array($recipientEmails) ? $recipientEmails : explode(',', $recipientEmails);
            $emailList = array_map('trim', $emailList);
            $emailList = array_filter($emailList); // Remove empty values

            if (empty($emailList)) {
                Log::warning('Registration Failure After Payment Admin Notification: No valid admin emails found');
                return false;
            }

            // Extract user data - handle both membership and conference formats
            $firstName = $userData['first_name'] ?? '';
            $lastName = $userData['last_name'] ?? '';
            $fullName = $userData['full_name'] ?? '';
            
            // If full_name is provided but first_name/last_name are not, use full_name
            if (empty($firstName) && empty($lastName) && !empty($fullName)) {
                $nameParts = explode(' ', $fullName, 2);
                $firstName = $nameParts[0] ?? '';
                $lastName = $nameParts[1] ?? '';
            }
            
            // If we have first_name/last_name but no full_name, construct it
            if (empty($fullName) && (!empty($firstName) || !empty($lastName))) {
                $fullName = trim($firstName . ' ' . $lastName);
            }

            $email = $userData['email_id'] ?? $userData['email'] ?? '';
            $phone = $userData['phone_number'] ?? $userData['mobile'] ?? $userData['mobile_no'] ?? '';
            
            // Extract payment data
            $registrationType = ucfirst($paymentData['registration_type'] ?? 'Registration');
            $amount = $paymentData['amount'] ?? 0;
            $currency = $paymentData['currency'] ?? 'INR';
            $orderId = $paymentData['gateway_order_id'] ?? $paymentData['order_id'] ?? 'N/A';
            $paymentId = $paymentData['gateway_transaction_id'] ?? $paymentData['payment_id'] ?? 'N/A';
            $transactionId = $paymentData['transaction_id'] ?? 'N/A';
            $paymentMethod = $paymentData['payment_method'] ?? 'Unknown';
            $failedDate = $paymentData['payment_date'] ?? $paymentData['created_on'] ?? date('Y-m-d H:i:s');
            
            // Extract error details from gateway_response if available
            $errorDetails = '';
            if (!empty($paymentData['gateway_response'])) {
                try {
                    $gatewayResponse = is_string($paymentData['gateway_response']) 
                        ? json_decode($paymentData['gateway_response'], true) 
                        : $paymentData['gateway_response'];
                    
                    if (is_array($gatewayResponse)) {
                        $errorParts = [];
                        if (!empty($gatewayResponse['error_description'])) {
                            $errorParts[] = 'Description: ' . $gatewayResponse['error_description'];
                        }
                        if (!empty($gatewayResponse['error_reason'])) {
                            $errorParts[] = 'Reason: ' . $gatewayResponse['error_reason'];
                        }
                        if (!empty($gatewayResponse['error_code'])) {
                            $errorParts[] = 'Code: ' . $gatewayResponse['error_code'];
                        }
                        $errorDetails = implode(' | ', $errorParts);
                    }
                } catch (\Exception $e) {
                    // Ignore parsing errors
                }
            }
            
            if (empty($errorDetails)) {
                $errorDetails = $failureReason;
            }

            $replacements = [
                '##REGISTRATION_TYPE##' => $registrationType,
                '##FULL_NAME##' => $fullName,
                '##FIRST_NAME##' => ucfirst($firstName),
                '##LAST_NAME##' => ucfirst($lastName),
                '##EMAIL##' => $email,
                '##PHONE##' => $phone,
                '##MOBILE##' => $phone,
                '##AMOUNT##' => number_format((float)$amount, 2),
                '##CURRENCY##' => $currency,
                '##ORDER_ID##' => $orderId,
                '##PAYMENT_ID##' => $paymentId,
                '##TRANSACTION_ID##' => $transactionId,
                '##PAYMENT_METHOD##' => ucfirst($paymentMethod),
                '##FAILURE_REASON##' => $failureReason,
                '##ERROR_DETAILS##' => $errorDetails,
                '##FAILED_DATE##' => date('d-m-Y H:i:s', strtotime($failedDate)),
            ];

            // Validate and filter email addresses
            $validEmails = [];
            foreach ($emailList as $adminEmail) {
                if (!empty($adminEmail) && filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
                    $validEmails[] = trim($adminEmail);
                }
            }

            if (empty($validEmails)) {
                Log::warning('Registration Failure After Payment Admin Notification: No valid admin emails found');
                return false;
            }

            // Send single email with all admins in CC for better performance
            $primaryRecipient = $validEmails[0];
            $ccRecipients = count($validEmails) > 1 ? array_slice($validEmails, 1) : [];

            $options = [];
            if (!empty($ccRecipients)) {
                $options['cc'] = implode(',', $ccRecipients);
            }

            $result = $this->sendEmailFromTemplate($templateId, $primaryRecipient, $replacements, $options);

            if ($result) {
                Log::info('Registration Failure After Payment Admin Notification sent', [
                    'template' => $templateId,
                    'primary_recipient' => $primaryRecipient,
                    'cc_recipients' => count($ccRecipients),
                    'registration_type' => $registrationType,
                    'user_email' => $email,
                    'payment_id' => $paymentId,
                    'transaction_id' => $transactionId
                ]);
                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Send Registration Failure After Payment Admin Notification Error: ' . $e->getMessage(), [
                'registration_type' => $paymentData['registration_type'] ?? 'N/A',
                'user_email' => $userData['email_id'] ?? $userData['email'] ?? 'N/A',
                'payment_id' => $paymentData['gateway_transaction_id'] ?? 'N/A',
                'error' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Send donation received notification to admin
     * 
     * @param array $donationData Donation data (must include: first_name, last_name, email, phone_number, donation_amount, currency, serial_number, etc.)
     * @param array $paymentData Payment/transaction data (gateway_transaction_id, gateway_order_id, transaction_id, payment_date, etc.)
     * @param int|null $templateId Template ID (default: uses EMAIL_TEMPLATE_DONATION_RECEIVED_ADMIN constant)
     * @param string|array $adminEmails Admin email(s) to notify (if not provided, fetches from database)
     * @return bool True if email sent successfully, false otherwise
     */
    protected function sendDonationReceivedAdminNotification($donationData, $paymentData = [], $templateId = null, $adminEmails = null)
    {
        try {
            // Use constant if template ID not provided
            if ($templateId === null) {
                $templateId = EMAIL_TEMPLATE_DONATION_RECEIVED_ADMIN;
            }

            // Get admin emails - priority: passed parameter > constant > template_otheremails
            $recipientEmails = $adminEmails;
            
            if (empty($recipientEmails)) {
                // Use constant from config/constants.php (comma-separated list)
                $recipientEmails = ADMIN_NOTIFICATION_EMAILS;
                
                // Convert to array if it's a string
                if (!empty($recipientEmails) && is_string($recipientEmails)) {
                    $recipientEmails = explode(',', $recipientEmails);
                    $recipientEmails = array_map('trim', $recipientEmails);
                    $recipientEmails = array_filter($recipientEmails); // Remove empty values
                    $recipientEmails = array_values($recipientEmails); // Re-index array
                }
            }
            
            // Fallback to template_otheremails if still empty
            if (empty($recipientEmails)) {
                $emailTemplate = Common_model::getDataFromTable(
                    'tbl_emailtemplates',
                    ['template_otheremails'],
                    ['id' => $templateId],
                    '',
                    '',
                    'ASC',
                    '',
                    0,
                    true,
                    ''
                );

                if (!empty($emailTemplate) && isset($emailTemplate[0]['template_otheremails'])) {
                    $recipientEmails = $emailTemplate[0]['template_otheremails'];
                }
            }

            if (empty($recipientEmails)) {
                Log::warning('Donation Received Admin Notification: No admin email configured');
                return false;
            }

            // Handle multiple emails (comma-separated or array)
            $emailList = is_array($recipientEmails) ? $recipientEmails : explode(',', $recipientEmails);
            $emailList = array_map('trim', $emailList);
            $emailList = array_filter($emailList); // Remove empty values

            if (empty($emailList)) {
                Log::warning('Donation Received Admin Notification: No valid admin emails found');
                return false;
            }

            // Extract donation data
            $firstName = $donationData['first_name'] ?? '';
            $lastName = $donationData['last_name'] ?? '';
            $fullName = trim($firstName . ' ' . $lastName);
            if (empty($fullName)) {
                $fullName = $donationData['full_name'] ?? '';
            }
            
            $email = $donationData['email'] ?? '';
            $phone = $donationData['phone_number'] ?? '';
            $donationAmount = $donationData['donation_amount'] ?? 0;
            $currency = $donationData['currency'] ?? 'INR';
            $currencySymbol = ($currency == 'USD') ? '$' : (($currency == 'EUR') ? '€' : (($currency == 'GBP') ? '£' : '₹'));
            $receiptNumber = $donationData['serial_number'] ?? 'N/A';
            $sourceType = $donationData['source_type'] ?? 'Website';
            
            // Extract payment data
            $orderId = $paymentData['gateway_order_id'] ?? 'N/A';
            $paymentId = $paymentData['gateway_transaction_id'] ?? 'N/A';
            $transactionId = $paymentData['transaction_id'] ?? 'N/A';
            $paymentDate = $paymentData['payment_date'] ?? $donationData['created_on'] ?? date('Y-m-d H:i:s');
            $paymentMethod = $paymentData['payment_method'] ?? 'N/A';
            $paymentGateway = $paymentData['payment_gateway'] ?? 'Razorpay';

            $replacements = [
                '##FULL_NAME##' => $fullName,
                '##FIRST_NAME##' => ucfirst($firstName),
                '##LAST_NAME##' => ucfirst($lastName),
                '##EMAIL##' => $email,
                '##PHONE##' => $phone,
                '##MOBILE##' => $phone,
                '##DONATION_AMOUNT##' => $currencySymbol . ' ' . number_format((float)$donationAmount, 2),
                '##AMOUNT##' => number_format((float)$donationAmount, 2),
                '##CURRENCY##' => $currency,
                '##CURRENCY_SYMBOL##' => $currencySymbol,
                '##RECEIPT_NUMBER##' => $receiptNumber,
                '##ORDER_ID##' => $orderId,
                '##PAYMENT_ID##' => $paymentId,
                '##TRANSACTION_ID##' => $transactionId,
                '##PAYMENT_METHOD##' => ucfirst($paymentMethod),
                '##PAYMENT_GATEWAY##' => $paymentGateway,
                '##SOURCE_TYPE##' => $sourceType,
                '##PAYMENT_DATE##' => date('d-m-Y H:i:s', strtotime($paymentDate)),
                '##DONATION_DATE##' => date('d-m-Y H:i:s', strtotime($paymentDate)),
            ];

            // Validate and filter email addresses
            $validEmails = [];
            foreach ($emailList as $adminEmail) {
                if (!empty($adminEmail) && filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
                    $validEmails[] = trim($adminEmail);
                }
            }

            if (empty($validEmails)) {
                Log::warning('Donation Received Admin Notification: No valid admin emails found');
                return false;
            }

            // Send single email with all admins in CC for better performance
            $primaryRecipient = $validEmails[0];
            $ccRecipients = count($validEmails) > 1 ? array_slice($validEmails, 1) : [];

            $options = [];
            if (!empty($ccRecipients)) {
                $options['cc'] = implode(',', $ccRecipients);
            }

            $result = $this->sendEmailFromTemplate($templateId, $primaryRecipient, $replacements, $options);

            if ($result) {
                Log::info('Donation Received Admin Notification sent', [
                    'template' => $templateId,
                    'primary_recipient' => $primaryRecipient,
                    'cc_recipients' => count($ccRecipients),
                    'donor_email' => $email,
                    'receipt_number' => $receiptNumber,
                    'amount' => $donationAmount,
                    'transaction_id' => $transactionId
                ]);
                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Send Donation Received Admin Notification Error: ' . $e->getMessage(), [
                'donor_email' => $donationData['email'] ?? 'N/A',
                'receipt_number' => $donationData['serial_number'] ?? 'N/A',
                'error' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    protected function sendEfiMemberVerificationOtpEmail($userData, $otpCode, $eventName = null, $otpExpiryMinutes = null, $templateId = null)
{
    try {
        // Use constant if template ID not provided
        if ($templateId === null) {
            $templateId = EMAIL_TEMPLATE_EFI_MEMBER_VERIFICATION_OTP;
        }

        // Use constant if OTP expiry not provided
        if ($otpExpiryMinutes === null) {
            $otpExpiryMinutes = OTP_EXPIRY_MINUTES;
        }

        $recipientEmail = $userData['email_id'] ?? null;
        if (empty($recipientEmail)) {
            Log::warning('Member Verification OTP: Recipient email is empty');
            return false;
        }

        if (empty($otpCode)) {
            Log::warning('Member Verification OTP: OTP code is empty');
            return false;
        }

        $replacements = [
            '##NAME##' => ucfirst($userData['first_name'] ?? ''),
            '##FIRST_NAME##' => ucfirst($userData['first_name'] ?? ''),
            '##LAST_NAME##' => ucfirst($userData['last_name'] ?? ''),
            '##FULL_NAME##' => ucfirst(trim(($userData['first_name'] ?? '') . ' ' . ($userData['last_name'] ?? ''))),
            '##OTP_CODE##' => $otpCode,
            '##OTP##' => $otpCode,
            '##OTP_EXPIRY##' => (string)$otpExpiryMinutes,
            '##USERNAME##' => $userData['email_id'] ?? $recipientEmail,
            '##EMAIL##' => $userData['email_id'] ?? $recipientEmail,
            '##EVENT_NAME##' => $eventName ?? 'Conference',
        ];

        return $this->sendEmailFromTemplate($templateId, $recipientEmail, $replacements);
    } catch (\Exception $e) {
        Log::error('Send Member Verification OTP Email Error: ' . $e->getMessage(), [
            'user_email' => $userData['email_id'] ?? 'N/A',
            'error' => $e->getTraceAsString()
        ]);
        return false;
    }
}

    protected function sendForgotPasswordEmail($userData, $otpExpiryMinutes = null, $templateId = null)
    {
        try {
            // Use constant if template ID not provided
            if ($templateId === null) {
                $templateId = EMAIL_TEMPLATE_RESET_PASSWORD;
            }

            $recipientEmail = $userData['email_id'] ?? null;
            if (empty($recipientEmail)) {
                Log::warning('Admin Reset Password : Recipient email is empty');
                return false;
            }

            $replacements = [
                '##USERNAME##' => ucfirst(($userData['first_name'] ?? '') . ' ' . ($userData['last_name'] ?? '')),
                '##EMAIL##' => $userData['email_id'] ?? $recipientEmail,
                '##PASSWORD##' => $userData['password'],
                '##RESETURL##' => url('resetpassword'),
            ];

            return $this->sendEmailFromTemplate($templateId, $recipientEmail, $replacements);
        } catch (\Exception $e) {
            Log::error('Send Admin Reset Password  Email Error: ' . $e->getMessage(), [
                'user_email' => $userData['email_id'] ?? 'N/A',
                'error' => $e->getTraceAsString()
            ]);
            return false;
        }
    }
}

