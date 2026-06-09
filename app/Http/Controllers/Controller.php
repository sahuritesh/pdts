<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Validator;
use App\Models\Common_model;
use App\Models\Datatables_model;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class Controller extends BaseController
{

    public $visit_date;

    public $chemicals_required;

    public $service_chemicals;

    public $response;
    public $log_upload_destination;
    public $custom_log;
    public $log_prefix;
    public $select;
   

    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function __construct()
    {
    
    }

    /**
     * Summary of _set_local_variables
     * @return void
     */
    private function _set_local_variables()
    {
        $this->visit_date = date("Y-m-d");
        $this->log_upload_destination = "uploads/logs/";
        $this->custom_log = Log::build([
            'driver' => 'daily',
            'path' => $this->log_upload_destination.date('Y-m-d').'/system.log',
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 30,
        ]);
        $this->log_prefix = "Class [class] Method [method] Line No [line_no]";
    }
}
