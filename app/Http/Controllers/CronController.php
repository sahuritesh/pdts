<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Common_model;
use App\Models\Datatables_model;
use Hash;
use Auth;
use DateTime;
use DateInterval;
use PDF;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;

class CronController extends Controller
{

    public function deleteGeneralLogs($type=''){
        try{
            $res = [];
            $log_data['start_time'] = current_datetime();
            if($type == 'logs'){
                $logPath = [storage_path('logs')];
                $days = env('PRUNE_LOGS_DAYS');
                $deletetype = 'directory';
            }elseif($type == 'apilogs'){
                $logPath = ['uploads/temp/','uploads/api/logs/','uploads/techTrackings/logs/','uploads/teamvisit/logs/'];
                $days = env('API_LOGS_DAYS');
                $deletetype = 'directory';
            }else if($type == 'captcha'){
                $logPath = ['uploads/work_reports/','captcha/'];
                $days = env('API_LOGS_DAYS');
                $deletetype = 'files';
            }
            $currentDate = new DateTime();
            $now = time();
            for($i=0;$i<count($logPath);$i++){
                if($deletetype == 'directory'){
                    $directories = File::directories($logPath[$i]);
                    foreach ($directories as $directory) {
                        $lastModifiedTimestamp = filemtime($directory);
                        $lastModifiedDate = date('Y-m-d H:i:s', $lastModifiedTimestamp);
                        $diffInDays = ($now - $lastModifiedTimestamp) / (60 * 60 * 24);
                        if ($diffInDays > $days) {
                            File::deleteDirectory($directory);
                            $res[] = "Deleted: $directory";
                        }
                    }
                }else if($deletetype == 'files'){
                    $files = File::files($logPath[$i]);
                    $i=0;
                    foreach ($files as $file) {
                        $filePath = $file->getPathname(); // Full path of the file
                        $lastModifiedTimestamp = filemtime($file);
                        $lastModifiedDate = date('Y-m-d H:i:s', $lastModifiedTimestamp);
                        $diffInDays = ($now - $lastModifiedTimestamp) / (60 * 60 * 24);
                        if ($diffInDays > $days) {
                            File::delete($filePath);
                            $res[] = "Deleted: $filePath";
                        }
                    }
                }
            }
            $log_data['description'] = (isset($res)) ? json_encode($res) : '';
            $log_data['type'] = 'File Unlink';
            $log_data['end_time'] = current_datetime();
            $log_data['created_on'] = current_datetime();
            $add = Common_model::addDataIntoTable('tbl_cron_logs', $log_data);
        }catch(Exception $e){
            \Log::error($e);
            $res['msg'] = $e;
        }
        echo json_encode($res);
    }
}
