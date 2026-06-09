<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Common_model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Http\Traits\WebResponseTrait;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SettingsController extends Controller
{
    use WebResponseTrait;

    public $smtp_settings = 'smtp_settings';
    public $settings = 'settings';
    public $razorpay_settings = 'razorpay_settings';

    /**
     * Display SMTP settings form
     */
    public function get_smtpsettings()
    {
        $res = permissionexists($this->smtp_settings);
        if ($res != '1') {
            return redirect()->back()->with('error', 'You dont have permission to access this page');
        }

        try {
            $pageTitle = 'Update SMTP Settings';
            $smtp_array = [];

            $smtp_settings = Common_model::getDataFromTable(
                'tbl_settings',
                '*',
                ['type' => 'SMTP'],
                '',
                '',
                'ASC',
                '',
                0,
                true,
                ''
            );

            if (!empty($smtp_settings)) {
                foreach ($smtp_settings as $settings) {
                    $smtp_array[$settings['title']] = $settings['description'];
                }
            }

            $data['smtp_settings'] = $smtp_array;

            return view('settings.smtp-settings', compact('pageTitle', 'data'));
        } catch (\Exception $e) {
            Log::error('Get SMTP Settings Error: ' . $e->getMessage());
            Log::error($e);
            return redirect()->back()->with('error', 'An error occurred while loading SMTP settings.');
        }
    }

    /**
     * Update SMTP settings
     */
    public function update_smtpsettings(Request $request)
    {
        try {
            $postData = $request->post();
            $errMessage = $this->validateSmtpSettings($postData);

            if (!empty($errMessage)) {
                $this->sendValidationErrorResponse($errMessage);
                return;
            }

            $result = $this->saveSmtpSettings($postData);

            if ($result) {
                $this->updateEnvFile($result);
                $this->sendSuccessResponse('SMTP Configuration updated successfully', 'Update');
            } else {
                $this->sendErrorResponse('SMTP Configuration not updated', 1);
            }
        } catch (\Exception $e) {
            Log::error('Update SMTP Settings Error: ' . $e->getMessage());
            Log::error($e);
            $this->sendErrorResponse($e->getMessage(), 2);
        }
    }

    /**
     * Display settings form
     */
    public function get_settings()
    {
        $res = permissionexists($this->settings);
        if ($res != '1') {
            return redirect()->back()->with('error', 'You dont have permission to access this page');
        }

        try {
            $pageTitle = 'Update Settings';
            $settings_array = [];

            $settings_data = Common_model::getDataFromTable(
                'tbl_settings',
                '*',
                ['type' => 'SETTINGS'],
                '',
                '',
                'ASC',
                '',
                0,
                true,
                ''
            );

            if (!empty($settings_data)) {
                foreach ($settings_data as $settings) {
                    $settings_array[$settings['title']] = $settings['description'];
                }
            }

            $data['settings'] = $settings_array;

            return view('settings.settings', compact('pageTitle', 'data'));
        } catch (\Exception $e) {
            Log::error('Get Settings Error: ' . $e->getMessage());
            Log::error($e);
            return redirect()->back()->with('error', 'An error occurred while loading settings.');
        }
    }

    /**
     * Update settings
     */
    public function update_settings(Request $request)
    {
        try {
            $postData = $request->post();
            $errMessage = $this->validateSettings($postData);

            if (!empty($errMessage)) {
                $this->sendValidationErrorResponse($errMessage);
                return;
            }

            $result = $this->saveSettings($postData);

            if ($result) {
                $this->sendSuccessResponse('Settings updated successfully', 'Update');
            } else {
                $this->sendErrorResponse('Settings not updated', 1);
            }
        } catch (\Exception $e) {
            Log::error('Update Settings Error: ' . $e->getMessage());
            Log::error($e);
            $this->sendErrorResponse($e->getMessage(), 2);
        }
    }

    /**
     * Validate SMTP settings data
     */
    private function validateSmtpSettings($postData)
    {
        $errMessage = '';
        $mandatoryFields = ['smtp_mailer', 'smtp_host', 'smtp_port', 'smtp_username', 'smtp_password', 'smtp_from_email'];

        foreach ($mandatoryFields as $fieldname) {
            $fieldValue = trim($postData[$fieldname] ?? '');
            if (empty($fieldValue)) {
                $fieldName = ucwords(strtolower(str_replace("_", " ", $fieldname)));
                $errMessage .= "<li>Please Enter $fieldName</li>";
            }
        }

        return $errMessage;
    }

    /**
     * Save SMTP settings to database
     */
    private function saveSmtpSettings($postData)
    {
        unset($postData['_token']);

        $fields = [
            'smtp_mailer',
            'smtp_host',
            'smtp_port',
            'smtp_username',
            'smtp_password',
            'smtp_encryption',
            'smtp_from_email'
        ];

        $smtpData = [];
        $currentUserId = Auth::user()->id ?? null;

        foreach ($fields as $field) {

            DB::table('tbl_settings')->updateOrInsert(
                ['title' => $field], // check if this key exists
                [
                    'description' => $postData[$field] ?? '',
                    'type'        => 'SMTP',                // set SMTP type
                    'updated_by'  => $currentUserId,
                    'updated_on'  => current_datetime()
                ]
            );
        }

        // Fetch updated/inserted settings for env update
        $smtpsettings = DB::table('tbl_settings')->where('type', 'SMTP')->get();
        foreach ($smtpsettings as $smtp) {
            $smtpData[$smtp->title] = $smtp->description;
        }

        return $smtpData;
    }


    /**
     * Update .env file with SMTP settings
     */
    private function updateEnvFile($smtpData)
    {
        $envMappings = [
            'smtp_mailer' => 'MAIL_MAILER',
            'smtp_host' => 'MAIL_HOST',
            'smtp_port' => 'MAIL_PORT',
            'smtp_username' => 'MAIL_USERNAME',
            'smtp_password' => 'MAIL_PASSWORD',
            'smtp_encryption' => 'MAIL_ENCRYPTION',
            'smtp_from_email' => 'MAIL_FROM_ADDRESS'
        ];

        $path = base_path('.env');

        if (file_exists($path)) {
            $envContent = file_get_contents($path);

            foreach ($envMappings as $settingKey => $envKey) {
                if (isset($smtpData[$settingKey])) {
                    $oldValue = env($envKey);
                    $newValue = $smtpData[$settingKey];

                    if ($oldValue !== false) {
                        $envContent = str_replace(
                            $envKey . '=' . $oldValue,
                            $envKey . '=' . $newValue,
                            $envContent
                        );
                    } else {
                        // Add new entry if it doesn't exist
                        $envContent .= "\n" . $envKey . '=' . $newValue;
                    }
                }
            }

            file_put_contents($path, $envContent);
        }
    }

    /**
     * Validate settings data
     */
    private function validateSettings($postData)
    {
        $errMessage = '';
        $mandatoryFields = [
            'title',
            'description',
            'membership_price',
            'allow_1_workshop_module_id',
            'allow_2_workshop_module_id',
            'Mobile_Android_Version',
            'Mobile_IOS_Version',
            'Mobile_Android_App_link',
            'Mobile_IOS_App_link'
        ];

        foreach ($mandatoryFields as $fieldname) {
            if (!is_array($postData[$fieldname] ?? null)) {
                $fieldValue = trim($postData[$fieldname] ?? '');
                if (empty($fieldValue)) {
                    $fieldName = ucwords(strtolower(str_replace("_", " ", $fieldname)));
                    $errMessage .= "<li>Please Enter $fieldName</li>";
                }
            }
        }

        return $errMessage;
    }

    /**
     * Save settings to database
     */
    private function saveSettings($postData)
    {
        unset($postData['_token']);

        $fields = [
            'title',
            'description',
            'membership_price',
            'allow_1_workshop_module_id',
            'allow_2_workshop_module_id',
            'Mobile_Android_Version',
            'Mobile_IOS_Version',
            'Mobile_Android_App_link',
            'Mobile_IOS_App_link'
        ];

        $currentUserId = Auth::user()->id ?? null;

        foreach ($fields as $field) {
            $check = Common_model::check_exists(
                'tbl_settings',
                'title',
                $field,
                '',
                ''
            );

            $postedData = [
                'description' => $postData[$field] ?? '',
                'updated_by' => $currentUserId,
                'updated_on' => current_datetime()
            ];

            if ($check == 0) {
                $postedData['type'] = 'SETTINGS';
                $postedData['title'] = $field;
                Common_model::addDataIntoTable('tbl_settings', $postedData);
            } else {
                Common_model::updateDataFromTable('tbl_settings', $postedData, 'title', $field);
            }
        }

        return true;
    }

    /**
     * Display Razorpay settings form
     */
    public function get_razorpay_settings()
    {
        $res = permissionexists($this->razorpay_settings);
        if ($res != '1') {
            return redirect()->back()->with('error', 'You dont have permission to access this page');
        }

        try {
            $pageTitle = 'Razorpay Payment Gateway Settings';
            $razorpay_array = [];

            // Get mode from database
            $mode_setting = Common_model::getDataFromTable(
                'tbl_settings',
                '*',
                ['type' => 'PAYMENT_GATEWAY', 'title' => 'razorpay_mode'],
                '',
                '',
                'ASC',
                '',
                0,
                true,
                ''
            );

            $current_mode = 'test'; // default
            if (!empty($mode_setting)) {
                $current_mode = $mode_setting[0]['description'] ?? 'test';
            }

            // Get credentials from .env (read-only, for display)
            $razorpay_array = [
                'razorpay_mode' => $current_mode,
                'test_key' => env('RAZORPAY_TEST_KEY', env('RAZORPAY_KEY', '')),
                'test_secret' => env('RAZORPAY_TEST_SECRET', env('RAZORPAY_SECRET', '')),
                'production_key' => env('RAZORPAY_PRODUCTION_KEY', env('RAZORPAY_KEY', '')),
                'production_secret' => env('RAZORPAY_PRODUCTION_SECRET', env('RAZORPAY_SECRET', ''))
            ];

            $data['razorpay_settings'] = $razorpay_array;

            return view('settings.razorpay-settings', compact('pageTitle', 'data'));
        } catch (\Exception $e) {
            Log::error('Get Razorpay Settings Error: ' . $e->getMessage());
            Log::error($e);
            return redirect()->back()->with('error', 'An error occurred while loading Razorpay settings.');
        }
    }

    /**
     * Update Razorpay mode
     */
    public function update_razorpay_mode(Request $request)
    {
        try {
            $postData = $request->post();
            $mode = trim($postData['razorpay_mode'] ?? '');

            // Debug logging
            Log::info('Razorpay Mode Update Request', [
                'all_post_data' => $postData,
                'razorpay_mode_value' => $mode,
                'is_empty' => empty($mode),
                'is_valid' => in_array($mode, ['test', 'production'])
            ]);

            // Validation
            if (empty($mode) || !in_array($mode, ['test', 'production'])) {
                $this->sendValidationErrorResponse('<li>Please select a valid mode (Test or Production)</li>');
                return;
            }

            $currentUserId = Auth::user()->id ?? null;

            // Save to database (same pattern as SMTP)
            DB::table('tbl_settings')->updateOrInsert(
                ['title' => 'razorpay_mode', 'type' => 'PAYMENT_GATEWAY'],
                [
                    'description' => $mode,
                    'updated_by' => $currentUserId,
                    'updated_on' => current_datetime()
                ]
            );

            // Clear cache to refresh credentials
            Cache::forget('razorpay_active_mode');
            Cache::forget('razorpay_active_credentials');

            $this->sendSuccessResponse('Razorpay mode updated successfully', 'Update');
        } catch (\Exception $e) {
            Log::error('Update Razorpay Mode Error: ' . $e->getMessage());
            Log::error($e);
            $this->sendErrorResponse($e->getMessage(), 2);
        }
    }
}
