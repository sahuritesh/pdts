<?php

namespace App\Http\Traits;

trait ApiResponseTrait
{
    /**
     * Return success response
     * 
     * @param mixed $data
     * @param string $message
     * @param int $statusCode
     * @return \Illuminate\Http\JsonResponse
     */
    protected function successResponse($data = null, $message = 'Success', $statusCode = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'status' => $statusCode
        ], $statusCode);
    }

    /**
     * Return error response
     * 
     * @param string $message
     * @param int $statusCode
     * @param mixed $errors
     * @return \Illuminate\Http\JsonResponse
     */
    protected function errorResponse($message = 'An error occurred', $statusCode = 400, $errors = null)
    {
        $response = [
            'success' => false,
            'message' => $message,
            'status' => $statusCode
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return validation error response
     * 
     * @param \Illuminate\Contracts\Validation\Validator $validator
     * @return \Illuminate\Http\JsonResponse
     */
    protected function validationErrorResponse($validator)
    {
        return $this->errorResponse(
            $validator->errors()->first(),
            422,
            $validator->errors()
        );
    }

    /**
     * Return not found response
     * 
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    protected function notFoundResponse($message = 'Resource not found')
    {
        return $this->errorResponse($message, 404);
    }

    /**
     * Return unauthorized response
     * 
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    protected function unauthorizedResponse($message = 'Unauthorized access')
    {
        return $this->errorResponse($message, 401);
    }

    /**
     * Return server error response
     * 
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    protected function serverErrorResponse($message = 'Internal server error')
    {
        return $this->errorResponse($message, 500);
    }
}

