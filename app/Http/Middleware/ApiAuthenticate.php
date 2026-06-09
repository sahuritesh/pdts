<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ApiAuthenticate
{
    /**
     * Handle an incoming request.
     * Validates user API token for authenticated API calls
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    
    public function handle(Request $request, Closure $next)
    {
        $apiToken = $request->bearerToken();
        
        if (empty($apiToken)) {
            return response()->json([
                'success' => false,
                'message' => 'The request does not contain the API token. Please provide a valid bearer token.',
                'data' => "",
                'status' => 401
            ], 401);
        }

        try {
            // Check if user exists with this API token and is active
            $loggedUser = DB::table('tbl_user')
                ->where('api_token', $apiToken)
                ->where('status', ACTIVE)
                ->where('is_mobile_enabled', 1)
                ->first();

            if ($loggedUser) {
                // Add user data to request attributes
                $request->attributes->add([
                    'loggedin_user' => [
                        [
                            'user_id' => $loggedUser->id,
                            'email_id' => $loggedUser->email_id,
                            'first_name' => $loggedUser->first_name,
                            'last_name' => $loggedUser->last_name,
                            'user_type' => $loggedUser->user_type,
                        ]
                    ]
                ]);
                
                return $next($request);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'The API token is no longer valid. Please log in again.',
                    'data' => "",
                    'status' => 401
                ], 401);
            }
        } catch (\Exception $e) {
            Log::error('ApiAuthenticate Middleware Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during authentication. Please try again later.',
                'data' => "",
                'status' => 500
            ], 500);
        }
    }
}
