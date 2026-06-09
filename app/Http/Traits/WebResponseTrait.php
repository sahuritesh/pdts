<?php

namespace App\Http\Traits;

trait WebResponseTrait
{
    /**
     * Send standardized success response
     * 
     * @param string $message Success message
     * @param string $operation Optional: 'Add' or 'Update'
     * @param string|null $redirectUrl Optional: URL to redirect after success
     * @return void
     */
    protected function sendSuccessResponse($message, $operation = '', $redirectUrl = null, $additionalData = [])
    {
        $res = [
            'error' => 0,
            'msg' => [0 => $message]
        ];

        if (!empty($operation)) {
            $res['operation'] = $operation;
        }

        if (!empty($redirectUrl)) {
            $res['redirect'] = $redirectUrl;
            $res['redirect_url'] = $redirectUrl; // Also include redirect_url for compatibility
        }

        // Merge any additional data
        if (!empty($additionalData) && is_array($additionalData)) {
            $res = array_merge($res, $additionalData);
        }

        echo json_encode($res);
    }

    /**
     * Send standardized validation error response
     * 
     * @param string $errorMessage Error message (can contain HTML <li> tags)
     * @return void
     */
    protected function sendValidationErrorResponse($errorMessage)
    {
        $res = [
            'error' => 1,
            'msg' => [0 => $errorMessage]
        ];

        echo json_encode($res);
    }

    /**
     * Send standardized exception/error response
     * 
     * @param string $errorMessage Error message
     * @param int $errorCode Error code (1 for validation, 2 for exception)
     * @return void
     */
    protected function sendErrorResponse($errorMessage, $errorCode = 2)
    {
        $res = [
            'error' => $errorCode,
            'msg' => [0 => $errorMessage]
        ];

        echo json_encode($res);
    }

    /**
     * Send standardized response with full control
     * 
     * @param int $error Error code (0=success, 1=validation, 2=exception)
     * @param string|array $message Message(s) - will be formatted as object with numeric keys
     * @param string $operation Optional: 'Add' or 'Update'
     * @param string|null $redirectUrl Optional: URL to redirect after success
     * @return void
     */
    protected function sendResponse($error, $message, $operation = '', $redirectUrl = null)
    {
        // Format message as object with numeric keys for parseFormErrors compatibility
        if (is_string($message)) {
            $formattedMessage = [0 => $message];
        } elseif (is_array($message)) {
            // If array, ensure numeric keys starting from 0
            $formattedMessage = [];
            $index = 0;
            foreach ($message as $msg) {
                $formattedMessage[$index] = $msg;
                $index++;
            }
        } else {
            $formattedMessage = [0 => (string)$message];
        }

        $res = [
            'error' => $error,
            'msg' => $formattedMessage
        ];

        if (!empty($operation)) {
            $res['operation'] = $operation;
        }

        if (!empty($redirectUrl)) {
            $res['redirect'] = $redirectUrl;
        }

        echo json_encode($res);
    }
}

