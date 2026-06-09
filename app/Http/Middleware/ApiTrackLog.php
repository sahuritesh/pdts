<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use App\Models\Admin;

class ApiTrackLog
{
    public $log_upload_destination;
    public $custom_log;
    public $class_name;
    public $route_action;
    public $request_track;
    public $response_track;
    public $source_array;

    public function __construct()
    {
        $this->log_upload_destination = "uploads/api/logs/";
        $this->source_array = [
            'driver' => 'daily',
            'path' => $this->log_upload_destination.date('Y-m-d').'/api_tracking.log',
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 30,
        ];
        //$this->class_name = Route::currentRouteName(); // string
        $this->route_action = Route::currentRouteAction(); // string
        $this->request_track = " Requested route [route] for [post_data] ";
        $this->response_track = " Response from the route [route] [response_data] ";
    }
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $api_token = $request->bearerToken();

        if(!is_null($api_token) && !empty($api_token))
        {
            $logged_user = Admin::where('api_token','=',$api_token)->get()->toArray();
            if(isset($logged_user[0]["user_id"]) && !empty($logged_user[0]["user_id"]))
            {
                $this->source_array["path"] = $this->log_upload_destination.date('Y-m-d')."/user_".$logged_user[0]["user_id"].'/api_tracking.log';
            }
            
        }

        $this->custom_log = Log::build($this->source_array);

        $this->custom_log->info(str_replace(array("[route]","[post_data]"),array($this->route_action,json_encode($request->post())),$this->request_track));

        $response = $next($request);

        if($response->headers->get('content-type') == "application/json")
        {
            $this->custom_log->info(str_replace(array("[route]","[response_data]"),array($this->route_action,$response->getContent()),$this->response_track));
        }

        return $response;
    }
}
