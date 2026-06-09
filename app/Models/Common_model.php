<?php

namespace App\Models;

use App\Support\PhpSpreadsheetExportFix;
use DateInterval;
use DatePeriod;
use DateTime;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PDF;
use Image;
use Crypt;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Carbon\Carbon;

class Common_model extends Model
{
    public static function addDataIntoTable($table = '', $data = [])
    {
        if ($table == '' || !count($data)) {
            return false;
        }
        try {
            DB::table($table)->insert($data);
            $inserted = DB::getPdo()->lastInsertId();
            self::AuditLogger($data, 'Insert', $inserted, $table, '');
            return $inserted;
        } catch (QueryException $e) {
            \Log::error($e);
            return false;
        }
    }
    
    public static function updateDataFromTable($table = '', $data = [], $field = '', $ID = 0)
    {
        $table = $table;
        if (empty($table) || !count($data)) {
            return false;
        } else {
            $update = DB::table($table);
            if (is_array($field)) {
                $update->where($field);
            } else {
                $update->where($field, $ID);
            }
            try {
                //self::AuditLogger($data, 'Update', $ID, $table, $field);
                return $update->update($data);
            } catch (QueryException $e) {
                \Log::error($e);
                return false;
            }
        }
    }
    
    public static function updateDataFromTabelWhereIn($table = '', $data = [], $where = [], $whereInField = '', $whereIn = [], $whereNotIn = false)
    {
        $table = $table;
        if (empty($table) || !count($data)) {
            return false;
        } else {
            $update = DB::table($table);
            if (is_array($where) && count($where) > 0) {
                $update->where($where);
            }
            if (is_array($whereIn) && count($whereIn) > 0 && $whereInField != '') {
                if ($whereNotIn) {
                    $update->whereNotIn($whereInField, $whereIn);
                } else {
                    $update->whereIn($whereInField, $whereIn);
                }
            }
			//echo $update->tosql();exit;
            try {
                return $update->update($data);
            } catch (QueryException $e) {
                \Log::error($e);
                return false;
            }
        }
    }
    
    public static function getDataFromTable($table = '', $field = '*', $whereField = '', $whereValue = '', $orderBy = '', $order = 'ASC', $limit = 0, $offset = 0, $resultInArray = false, $groupBy = '')
    {
        try {
            $table = $table;
            $getData = DB::table($table)->select($field);
            if (is_array($whereField)) {
                foreach($whereField as $key => $value)
                {
                    if(is_array($value))
                    {
                        if(is_int($key) && count($value) == 3 && isset($value[0]) && isset($value[1]) && isset($value[2]))
                        {
                            $getData->where($value[0],$value[1],$value[2]);
                            continue;
                        }
                        $getData->whereIn($key,$value);
                        continue;
                    }
                    
                    if($key == 'start_date')
                    {
                        $getData->where('service_date','>=',$value);
                        continue;
                    }
                    if($key == 'end_date')
                    {
                        $getData->where('service_date','<=',$value);
                        continue;
                    }

                    $getData->where($key,'=',$value);
                }
            } elseif (!empty($whereField) && $whereValue != '') {
                $getData->where($whereField, $whereValue);
            }
            if (!empty($orderBy)) {
                $getData->orderBy($orderBy, $order);
            }
            if (!empty($groupBy)) {
                $getData->groupBy($groupBy);
            }
            if ($limit > 0) {
                $getData->offset($offset)->limit($limit);
            }
            // print_r($whereField);
            //echo $getData->toSql();exit;
            // echo "<br>";exit;
            $result = $getData->get();
            if ($resultInArray) {
                $result = collect($result)
                ->map(function ($x) {
                    return (array) $x;
                })
                ->toarray();
            }
            //print_r($result);die();
            if (!empty($result)) {
                return $result;
            } else {
                return false;
            }
        } catch (QueryException $e) {
            \Log::error($e);
            return false;
        }
    }
    
    public static function getDataFromTableWhereWhereIn($table = '', $field = '*', $where = '', $whereinField = '', $whereinValue = '', $orderBy = '', $whereNotIn = 0)
    {
        $table = $table;
        $getData = DB::table($table);
        $getData->select($field);
        if ($whereNotIn > 0 && is_array($where)) {
            $getData->where($where)->whereNotIn($whereinField, $whereinValue);
        } elseif (is_array($where) && count($whereinValue) > 0) {
            $getData->where($where)->whereIn($whereinField, $whereinValue);
        } elseif (is_array($where) && count($whereinValue) == 0) {
            $getData->where($where);
        } elseif (!is_array($where)) {
            $getData->whereIn($whereinField, $whereinValue, false);
        }
        
        if (!empty($orderBy)) {
            $getData->orderBy($orderBy);
        }
        // print_r($where);         print_r($whereinValue); echo count($whereinValue);    echo $getData->toSql(); exit;
        $result = $getData->get();
        $result = collect($result)
        ->map(function ($x) {
            return (array) $x;
        })
        ->toarray();
        if (!empty($result)) {
            return $result;
        } else {
            return false;
        }
    }
    
    public static function getDataFromTableWhereIn($table = '', $field = '*', $whereField = '', $whereValue = '', $orderBy = '', $order = 'ASC', $whereNotIn = 0)
    {
        try {
            $table = $table;
            $getData = DB::table($table);
            $getData->select($field);
            if ($whereNotIn > 0) {
                $getData->whereNotIn($whereField, $whereValue);
            } else {
                $whereValue = $whereValue;
                $getData->whereIn($whereField, $whereValue, false);
            }
            if (is_array($orderBy) && count($orderBy)) {
                /* $orderBy treat as where condition if $orderBy is array  */
                $getData->where($orderBy);
            } elseif (!empty($orderBy)) {
                $getData->orderBy($orderBy, $order);
            }
            //echo $getData->toSql();exit;
            $result = $getData->get();
            $result = collect($result)
            ->map(function ($x) {
                return (array) $x;
            })
            ->toarray();
            if (!empty($result)) {
                return $result;
            } else {
                return false;
            }
        } catch (QueryException $e) {
            \Log::error($e);
            return false;
        }
    }
    
    public static function countResult($table = '', $field = '', $value = '', $limit = 0, $groupBy = '',$joinsArray='',$whereIn='')
    {
        try {
            $result = DB::table($table);
            if (!empty($groupBy)) {
                $result->select($groupBy);
            }
            if (isset($joinsArray) && !empty($joinsArray) && is_array($joinsArray) && sizeof($joinsArray) > 0) {
                // if(!empty($joinsArray)){
                foreach ($joinsArray as $each) {
                    $conditionArray = explode('=', $each['condition']);
                    if ($each['join_type'] == 'inner' || empty($each['join_type'])) {
                        $result->join($each['table_name'], $conditionArray[0], '=', $conditionArray[1]);
                    } else {
                        $joinType = $each['join_type'] . 'Join';
                        $result->$joinType($each['table_name'], $conditionArray[0], '=', $conditionArray[1]);
                    }
                }
            }
            if (is_array($field)) {
                $result->where($field);
            } elseif (!empty($field) && $value != '') {
                $result->where($field, $value);
            }
            if(is_array($whereIn) && count($whereIn)>0){
                foreach ($whereIn as $WhereInKey => $WhereInValue) {
                    $result->whereIn($WhereInKey, $WhereInValue);
                }
            }
            if (!empty($groupBy)) {
                $result->groupBy($groupBy);
            }
            if ($limit > 0) {
                $result->offset(0)->limit($limit);
            }
            //echo $result->toSql();exit;
            $res = $result->get();
            return $res->count();
        } catch (QueryException $e) {
            \Log::error($e);
            return false;
        }
    }
    
    public static function deleteRowFromTable($table = '', $field = '', $ID = 0, $limit = 0)
    {
        try {
            $table = $table;
            $Flag = false;
            $delete = DB::table($table);
            if ($table != '' && $field != '') {
                if (is_array($ID) && count($ID)) {
                    $delete->where_in($field, $ID);
                } elseif (is_array($field) && count($field) > 0) {
                    $delete->where($field);
                } else {
                    $delete->where($field, $ID);
                }
                if ($limit > 0) {
                    $delete->ofset(0)->limit($limit);
                }
                if ($delete->delete()) {
                    $Flag = true;
                }
            }
            return $Flag;
        } catch (QueryException $e) {
            \Log::error($e);
            return false;
        }
    }
    
    public static function deletelWhereWhereIn($table = '', $where = '', $whereinField = '', $whereinValue = '', $whereNotIn = 0)
    {
        try {
            $table = $table;
            $delete = DB::table($table);
            if (is_array($where)) {
                $delete->where($where);
            }
            if ($whereNotIn > 0) {
                $delete->whereNotIn($whereinField, $whereNotIn);
            } else {
                $delete->whereIn($whereinField, $whereinValue);
            }
            if ($delete->delete()) {
                return true;
            } else {
                return false;
            }
        } catch (QueryException $e) {
            \Log::error($e);
            return false;
        }
    }
    
    public static function check_exists($table, $column, $value, $whereField, $whereValue)
    {
        try {
            $count = DB::table($table);
            if (is_array($column)) {
                $count->where($column);
            } else {
                $count->where($column, $value);
            }
            if ($whereValue) {
                $count->whereNotIn($whereField, $whereValue);
            }
            //echo $count->tosql();exit;
            $res = $count->get();
            return $res->count();
        } catch (QueryException $e) {
            \Log::error($e);
            return false;
        }
    }
    
    public static function check_exists_based_ids($table, $whereField, $whereValue, $column1 = '', $value1 = '', $column2 = '', $value2 = '', $column3 = '', $value3 = '', $column4 = '', $value4 = '')
    {
        try {
            $check = DB::table($table);
            if (!empty($value1)) {
                $check->where($column1, $value1);
            }
            if (!empty($value2)) {
                $check->where($column2, $value2);
            }
            if (!empty($value3)) {
                $check->where($column3, $value3);
            }
            if (!empty($value4)) {
                $check->where($column4, $value4);
            }
            if ($whereValue) {
                $check->whereNotIn($whereField, $whereValue);
            }
            //echo $check->toSql();exit;
            $res = $check->get();
            //print_r($res);die();
            return $res->count();
        } catch (QueryException $e) {
            \Log::error($e);
            return false;
        }
    }
    
    public static function getRandomString()
    {
        return $my_rand_strng = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyz"), -10);
    }
    
    public static function generateQRCode($new_usertype_code, $folder)
    {
        if (!file_exists('public/uploads/qrcode/' . $folder)) {
            mkdir('public/uploads/qrcode/' . $folder, 0755, true);
        }
        $path = base_path('public/uploads/qrcode/' . $folder . '/QRCode-' . time() . '.jpg');
        $qrcode_path = 'public/uploads/qrcode/' . $folder . '/QRCode-' . time() . '.jpg';
        QrCode::format('png');
        $image = \QrCode::format('png')
        ->size(300)
        ->backgroundColor(255, 255, 255)
        ->color(0, 0, 0)
        // ->style('dot')
        // ->eye('circle')
        ->generate($new_usertype_code, $path);
        return $qrcode_path;
    }
    
    public static function commonApiSystem($endPoint, $requestBody)
    {
        $accessCommonArray = ['Username' => '', 'Password' => ''];
        //echo "<pre>";
        $finalRequestBody = array_merge($accessCommonArray, $requestBody);
        
        $response = self::executeCurlRequest($endPoint, $requestBody);
        return $response;
    }
    public static function executeCurlRequest($endPoint, $requestBody)
    {
        try {
            $curl = curl_init($endPoint);
            curl_setopt($curl, CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($curl, CURLOPT_POST, true);
            
            curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json']);
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($requestBody));
            
            $json_response = curl_exec($curl);
            $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $err = curl_error($curl);
            curl_close($curl);
            return json_decode($json_response, 1);
        } catch (\Exception $e) {
            \Log::error($e);
            return false;
        }
    }
    
    public static function getMasterdata($data = null)
    {
        extract($data);
        $masterdata = self::getDataFromTable($table = $table, $field = '*', $whereField = $where, $whereValue = '', $orderBy = '', $order = 'ASC', $limit = 0, $offset = 0, $resultInArray = false, $groupBy = '');
        return $masterdata;
    }
    public static function exportExcel($data, $filename)
    {
        try {
            PhpSpreadsheetExportFix::ensureBeforeXlsxExport();

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            $sheet->fromArray($data, null, 'A1');
            
            // Create a new Xlsx writer instance
            $writer = new Xlsx($spreadsheet);
            
            // Generate the file content
            ob_start();
            $writer->save('php://output');
            $fileContent = ob_get_clean();
            
            // Set headers for the download
            $filename = $filename;
            if (ob_get_level() > 0) {
                ob_end_clean();
            }
            $headers = [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];
            
            // Return a response with the generated file content and headers
            return new Response($fileContent, 200, $headers);
        } catch (\Exception $e) {
            \Log::error($e);
            return false;
        }
    }
    
    public static function check_valid_email($email)
    {
        $regex = '/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})$/';
        if (preg_match($regex, $email)) {
            $res = '1';
        } else {
            $res = '0';
        }
        return $res;
    }
    
    public static function check_telephone($telephone)
    {
        $pattern = '/^-?\d+(?:-\d+)?$/';
        // Use preg_match to check if the telephone matches the pattern
        if (preg_match($pattern, $telephone)) {
            $res = '1'; // Valid input
        } else {
            $res = '0'; // Invalid input
        }
        return $res;
    }
    
    public static function check_valid_password($password)
    {
        $number = preg_match('@[0-9]@', $password);
        $uppercase = preg_match('@[A-Z]@', $password);
        $lowercase = preg_match('@[a-z]@', $password);
        $specialChars = preg_match('@[^\w]@', $password);
        if (strlen($password) < 8 && !$number && !$uppercase && !$lowercase && !$specialChars) {
            $msg = '0';
        } else {
            $msg = '1';
        }
        return $msg;
    }
    
    public static function stringencryption($string)
    {
        $encrpted = md5($string);
        return $encrpted;
    }
    
    public static function getResultforAPIPagination(
        $selectColumns, $dataTableSortOrdering, $table_name, $joinsArray, 
        $wherecondition, $indexColumn = '', $orderByColum = '', $sortType = '', 
        $limit = '', $offset = '', $resultInArray = false, $serachColumns = '', 
        $searchValue = '', $whereIn='', $groupBy='', $dis_qry=false)
    {
        try {
            //DB::enableQueryLog();
            $result = DB::table($table_name);
            $result->select($selectColumns);
    
            // Join Query
            if (!empty($joinsArray) && sizeof($joinsArray) > 0) {
                foreach ($joinsArray as $each) {
                    $conditionArray = explode('=', $each['condition']);
                    if ($each['join_type'] == 'inner' || empty($each['join_type'])) {
                        $result->join($each['table_name'], $conditionArray[0], '=', $conditionArray[1]);
                    } else {
                        $joinType = $each['join_type'] . 'Join';
                        $result->$joinType($each['table_name'], $conditionArray[0], '=', $conditionArray[1]);
                    }
                }
            }
            
            // Applying Filter Query
            if (!empty($wherecondition)) {
                foreach ($wherecondition as $key => $value) {
                    if (stripos($key, "created_on") || stripos($key, "updated_on")) {
                        if (!is_null($value) && isset($value['value'])) {
                            $result->whereDate($key, $value['value']);
                        }
                    } else {
                        $conditions = preg_split('/(<=|>=|!=|<|>|!)/', $key, -1, PREG_SPLIT_DELIM_CAPTURE);
                        if(isset($conditions[1]) && !empty($conditions[1]))
                        {
                            $result->where(str_replace($conditions[1], "", $key), $conditions[1], $value);
                        }else {
                            if (isset($value['column'])) { 
                                $column = $value['column'];
                                $condition = $value['condition'];
                                $cast = isset($value['cast']) ? $value['cast'] : null;
                                if ($cast) {
                                    $column = DB::raw("CAST($column AS $cast)");
                                }
                                $result->where($column, $condition, $value['value']);
                            } else {
                                $result->where($key, $value);
                            }
                        }
                    }
                }
            }
            
            if (is_array($whereIn) && count($whereIn) > 0) {
                foreach ($whereIn as $WhereInKey => $WhereInValue) {
                    $result->whereIn($WhereInKey, $WhereInValue);
                }
            }
    
            if (!empty($searchValue)) {
                $search = stripslashes($searchValue);
                if (!empty($search) && is_array($serachColumns)) {
                    foreach ($serachColumns as $key => $eachColumn) {
                        $tableclmn = explode(' as ', $eachColumn);
                        $eachColumn = $tableclmn[0];
                        if ($key == 0) {
                            $result->where($eachColumn, 'like', "%" . $search . "%");
                        } else {
                            $result->orwhere($eachColumn, 'like', "%" . $search . "%");
                        }
                    }
                }
            }
    
            // Sorting Query
            if (!empty($dataTableSortOrdering)) {
                foreach ($dataTableSortOrdering as $key => $value) {
                    if (!is_null($value)) {
                        $result->orderBy($key, $value);
                    }
                }
            } elseif ($orderByColum != '' && $sortType != '') {
                $result->orderBy($orderByColum, $sortType);
            } else {
                $result->orderBy($orderByColum, 'desc');
            }
    
            if (!empty($groupBy)) {
                $result->groupBy($groupBy);
            }
    
            if($dis_qry){
                print_r($wherecondition);
                echo $result->toSql();exit;
            }
          //  echo $result->toSql();exit;
            $result = $result->skip($offset)->take($limit)->get();
    
            if ($resultInArray) {
                $result = collect($result)->map(function ($x) {
                    return (array) $x;
                })->toArray();
            }
    
            if (!empty($result)) {
                return $result;
            } else {
                return false;
            }
        } catch (QueryException $e) {
            \Log::error($e);
            return false;
        }
    }
    
    public static function AuditLogger($postData, $action, $id, $tableName, $field)
    {
        try {
            // Check if audit log table exists before attempting to log
            // This prevents errors when the table doesn't exist
            $tableExists = DB::select("SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'tbl_audit_log'");
            if (empty($tableExists) || (isset($tableExists[0]) && isset($tableExists[0]->cnt) && $tableExists[0]->cnt == 0)) {
                // Table doesn't exist - silently skip audit logging
                return true;
            }
            
            $post_keys = array_keys($postData);
            if ($action == 'Update') {
                if (is_array($field)) {
                    $where = $field;
                } else {
                    $where = [$field => $id];
                }
                
                $old_rec = self::getDataFromTable($tableName, $post_keys, $where, '', '', '', 0, 0, true, '');
                if ($old_rec) {
                    $old_rec = $old_rec[0];
                    for ($i = 0; $i < count($post_keys); $i++) {
                        $new_value = $postData[$post_keys[$i]];
                        if ($old_rec[$post_keys[$i]] != $new_value) {
                            $old_data[$post_keys[$i]] = $old_rec[$post_keys[$i]];
                        }
                    }
                }
            }
            
            $audit_log = [
                'record_id' => $id,
                'wherecondition' => (is_array($field)) ? json_encode($field) : '',
                'tbl_name' => $tableName,
                'action' => $action,
                'new_data' => json_encode($postData),
                'old_data' => !empty($old_data) ? json_encode($old_data) : '',
                'created_on' => current_datetime(),
            ];
            if (Auth::user()) {
                // Use tbl_user.id (primary key) for audit logs
                $audit_log['created_by'] = Auth::id();
            } else {
                if ($action == 'Insert' && isset($postData['created_by'])) {
                    $audit_log['created_by'] = $postData['created_by'];
                } else if ($action == 'Update' && isset($postData['updated_by'])) {
                    $audit_log['created_by'] = $postData['updated_by'];
                }
            }
            DB::table('tbl_audit_log')->insert($audit_log);
            return true;
        } catch (QueryException $e) {
            // Only log if it's not a "table doesn't exist" error
            if (strpos($e->getMessage(), 'Invalid object name') === false && strpos($e->getMessage(), 'does not exist') === false) {
                \Log::error('AuditLogger QueryException: ' . $e->getMessage());
            }
            return false;
        } catch (\Exception $e) {
            // Only log if it's not a "table doesn't exist" error
            if (strpos($e->getMessage(), 'Invalid object name') === false && strpos($e->getMessage(), 'does not exist') === false) {
                \Log::error('AuditLogger Exception: ' . $e->getMessage());
            }
            return false;
        }
    }
    
    public static function getCommaSeparatedFields($reqiredFields = [], $dataArray = [], $indexID = '')
    {
        $finalArray = [];
        if ($indexID == '') {
            $indexID = 'id';
        }
        if (isset($dataArray) && sizeof($dataArray) > 0) {
            $tempArray = [];
            foreach ($dataArray as $eachRow) {
                if (isset($eachRow[$indexID])) {
                    $index = $eachRow[$indexID];
                    $finalArray['results'][$index] = $eachRow;
                    foreach ($eachRow as $key => $value) {
                        if (in_array($key, $reqiredFields)) {
                            if ($key == 'reference_number') {
                                if ($value != '') {
                                    if (isset($tempArray[$key]) && $tempArray[$key] != '') {
                                        $tempArray[$key] = $tempArray[$key] . $value . ',';
                                    } else {
                                        $tempArray[$key] = $value . ',';
                                    }
                                }
                            } else {
                                if ($value != '' && $value > 0) {
                                    if (isset($tempArray[$key]) && $tempArray[$key] != '') {
                                        $tempArray[$key] = $tempArray[$key] . $value . ',';
                                    } else {
                                        $tempArray[$key] = $value . ',';
                                    }
                                }
                            }
                        }
                    }
                }
            }
            if (isset($tempArray) && sizeof($tempArray) > 0) {
                foreach ($tempArray as $key => $value) {
                    $trimmedValue = trim($value, ',');
                    $finalArray['commaSeparated'][$key] = $trimmedValue;
                }
            }
        }
        
        return $finalArray;
    }
    
    /**
     * Legacy method - Updated to use new FirebaseNotificationService
     * 
     * @param string|array $tokens Device token(s)
     * @param array $message Notification message with 'title' and 'body' keys
     * @param array $data Additional data payload
     * @return array|string Results
     */
    public static function firebase_notification($tokens, $message, $data)
    {
        try {
            $firebaseService = app(\App\Services\FirebaseNotificationService::class);
            
            // Extract title and body from message array
            $title = $message['title'] ?? $message['notification']['title'] ?? 'Notification';
            $body = $message['body'] ?? $message['notification']['body'] ?? $message['message'] ?? '';
            
            // Handle tokens - can be string or array
            $tokensArray = is_array($tokens) ? $tokens : [$tokens];
            
            $result = $firebaseService->sendToMultipleDevices($tokensArray, $title, $body, $data);
            
            // Return JSON string for backward compatibility
            return json_encode($result);
        } catch (\Exception $e) {
            \Log::error('Error in firebase_notification: ' . $e->getMessage());
            return json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
   
    public static function updateDataFromTableNUll($table = '', $data = [], $field = '', $ID = 0)
    {
        if (empty($table) || !count($data)) {
            return false;
        }
        
        $update = DB::table($table);
        
        // Check if $field is an array and handle null conditions
        if (is_array($field)) {
            foreach ($field as $key => $value) {
                if (is_null($value)) {
                    $update->whereNull($key);
                } elseif ($value === 'NOT_NULL') {
                    $update->whereNotNull($key);
                } else {
                    $update->where($key, $value);
                }
            }
        } else {
            $update->where($field, $ID);
        }
        
        try {
            // self::AuditLogger($data, 'Update', $ID, $table, $field);
            return $update->update($data);
        } catch (QueryException $e) {
            \Log::error($e);
            return false;
        }
    }

    public static function sendNotificationToUser($tokens, $title, $description)
    {
        try {
            // Use the new FirebaseNotificationService
            $firebaseService = app(\App\Services\FirebaseNotificationService::class);
            
            // $tokens can be a single token (string) or array of tokens
            $tokensArray = is_array($tokens) ? $tokens : [$tokens];
            
            $result = $firebaseService->sendToMultipleDevices($tokensArray, $title, $description, []);
            
            return response()->json([
                'message' => 'Notification has been sent',
                'response' => $result,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error sending Firebase notification: ' . $e->getMessage());
            throw $e;
        }
    }
}
    