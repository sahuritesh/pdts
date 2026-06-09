<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Common_model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Http\Traits\WebResponseTrait;
use App\Http\Traits\EmailTrait;
use Illuminate\Support\Facades\Log;

class ForgotPasswordController extends Controller
{
    use WebResponseTrait, EmailTrait;

    /**
     * Display forgot password form
     */
    public function forgotpassword()
    {
        $pageTitle = "CMS Forgot Password";
        return view('layouts.forgotpassword', compact('pageTitle'));
    }

    /**
     * Submit forgot password request
     */
    public function submitforgotpassword(Request $request)
    {
        try {
            $email = trim($request->input('email_id', ''));

            if (empty($email)) {
                return redirect('forgetpassword')->with('error', 'Please Enter Email Id');
            }

            // Get user details from tbl_user table
            $userDetails = Common_model::getDataFromTable(
                'tbl_user',
                ['id', 'email_id', 'first_name', 'status'],
                ['email_id' => $email],
                '',
                '',
                'ASC',
                '',
                0,
                true,
                ''
            );

            if (empty($userDetails) || !isset($userDetails[0])) {
                return redirect('forgetpassword')->with('error', 'Please Enter a Valid Email Id');
            }

            $user = $userDetails[0];

            // Check if user is active
            if (empty($user['status']) || $user['status'] != ACTIVE) {
                return redirect('forgetpassword')->with('error', 'This user account is inactive. Please contact support.');
            }

            // Generate password token
            $newcode = Str::random(6);
            $usr_data = [
                'password_token' => $newcode,
                'pwd_token_created_on' => current_datetime()
            ];

            $updateResult = Common_model::updateDataFromTable('tbl_user', $usr_data, 'email_id', $email);

            if ($updateResult) {
                // Send email with OTP
                $emailSent = $this->sendPasswordResetEmail($user, $newcode);

                if ($emailSent) {
                    return redirect('forgetpassword')->with('success', 'OTP for Reset Password Sent to Registered Email');
                } else {
                    return redirect('forgetpassword')->with('error', 'Email Failed. Please Try again after sometime');
                }
            } else {
                return redirect('forgetpassword')->with('error', 'Failed to generate reset token. Please try again.');
            }
        } catch (\Exception $e) {
            Log::error('Submit Forgot Password Error: ' . $e->getMessage());
            Log::error($e);
            return redirect('forgetpassword')->with('error', 'An error occurred. Please try again later.');
        }
    }

    /**
     * Display reset password form
     */
    public function resetpassword()
    {
        $pageTitle = "CMS Reset Password";
        return view('layouts.resetpassword', compact('pageTitle'));
    }

    /**
     * Submit reset password
     */
    public function submitresetpassword(Request $request)
    {
        try {
            $postData = $request->post();
            $errMessage = $this->validateResetPasswordData($postData);

            if (!empty($errMessage)) {
                $this->sendValidationErrorResponse($errMessage);
                return;
            }

            $email = trim($postData['email_id']);
            $otp = trim($postData['otp']);
            $newPassword = trim($postData['new_password']);
            $confirmPassword = trim($postData['confirm_password']);

            // Validate password match
            if ($newPassword !== $confirmPassword) {
                $this->sendErrorResponse("New Password And Confirm Password Didn't Matched", 1);
                return;
            }

            // Get user details
            $userDetails = Common_model::getDataFromTable(
                'tbl_user',
                ['id', 'email_id', 'password', 'password_token', 'pwd_token_created_on', 'status'],
                ['email_id' => $email],
                '',
                '',
                'ASC',
                '',
                0,
                true,
                ''
            );

            if (empty($userDetails) || !isset($userDetails[0])) {
                $this->sendErrorResponse('Please Enter a Registered Email Id', 1);
                return;
            }

            $user = $userDetails[0];

            // Check if user is active
            if (empty($user['status']) || $user['status'] != ACTIVE) {
                $this->sendErrorResponse('This user account is inactive. Please contact support.', 1);
                return;
            }

            // Verify OTP
            if (empty($user['password_token']) || $user['password_token'] !== $otp) {
                $this->sendErrorResponse('Invalid OTP. Please check and try again.', 1);
                return;
            }

            // Check if OTP is expired (24 hours)
            if (!empty($user['pwd_token_created_on'])) {
                $tokenCreated = strtotime($user['pwd_token_created_on']);
                $currentTime = time();
                $hoursDiff = ($currentTime - $tokenCreated) / 3600;

                if ($hoursDiff > 24) {
                    $this->sendErrorResponse('OTP has expired. Please request a new OTP.', 1);
                    return;
                }
            }

            // Check if new password is same as current password
            if (Hash::check($newPassword, $user['password'])) {
                $this->sendErrorResponse('Your New Password and Current Password Both Are Same', 1);
                return;
            }

            // Update password and clear token
            $updateData = [
                'password' => Hash::make($newPassword),
                'password_token' => null,
                'pwd_token_created_on' => null,
                'updated_on' => current_datetime()
            ];

            $updateResult = Common_model::updateDataFromTable('tbl_user', $updateData, 'email_id', $email);

            if ($updateResult) {
                $this->sendSuccessResponse('Password Reset Successful', 'Update');
            } else {
                $this->sendErrorResponse('Failed to reset password. Please try again.', 1);
            }
        } catch (\Exception $e) {
            Log::error('Submit Reset Password Error: ' . $e->getMessage());
            Log::error($e);
            $this->sendErrorResponse('An error occurred while processing your request', 2);
        }
    }

    /**
     * Validate reset password data
     */
    private function validateResetPasswordData($postData)
    {
        $errMessage = '';
        $mandatoryFields = ['email_id', 'otp', 'new_password', 'confirm_password'];

        foreach ($mandatoryFields as $fieldname) {
            $fieldValue = trim($postData[$fieldname] ?? '');
            if (empty($fieldValue)) {
                $fieldName = ucwords(strtolower(str_replace("_", " ", $fieldname)));
                $errMessage .= "<li>Please Enter $fieldName</li>";
            }
        }

        // Validate password strength if password is provided
        if (!empty($postData['new_password'])) {
            $passwordValid = Common_model::check_valid_password($postData['new_password']);
            if ($passwordValid == '0') {
                $errMessage .= '<li>Password must be at least 8 characters in length and must contain at least one number, one upper case letter, one lower case letter and one special character.</li>';
            }
        }

        return $errMessage;
    }

    /**
     * Send password reset email with OTP
     */
    private function sendPasswordResetEmail($user, $otp)
    {
        try {
            $userDataForEmail = [
                'first_name' => $user['first_name'] ?? '',
                'last_name' => $user['last_name'] ?? '',
                'email_id' => $user['email_id'],
            ];

            return $this->sendForgotPasswordOtpEmail($userDataForEmail, $otp);
        } catch (\Exception $e) {
            Log::error('Send Password Reset Email Error: ' . $e->getMessage());
            Log::error($e);
            return false;
        }
    }
}
