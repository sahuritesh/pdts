<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Common_model;
use App\Models\Datatables_model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Redirect;

class Common_controller extends Controller
{
    public function changestatus(Request $request)
    {
        $postData = [];
        $requestData = $request->getContent();
        $postData = json_decode($requestData, true); // Decode the JSON data
        $table = $postData['table'];
        if (!$this->hasTablePermission($table)) {
            return response()->json([
                'error' => 1,
                'msg' => 'Permission missing. Contact administrator.'
            ]);
        }
        $field = 'status';
        $status = $postData['status'];
        $postData['status'] = $postData['status'];
        $postData['updated_by'] = Auth::id(); // Use tbl_user.id (primary key)
        $postData['updated_on'] = current_datetime();
        $id = Crypt::decrypt($postData['id']);
        unset($postData['token']);
        unset($postData['id']);
        unset($postData['table']);
        $where['id'] = $id;
        $where['status'] = $postData['status'];	   
        
        $isExist = Common_model::check_exists($table, $where, '', 'id', array());
        if($isExist == 0){
            if ($status == ACTIVE){
                    $msg = "Status Activated successfully!!";
                }else if($status == INACTIVE){ 
                    $msg = "Status De-Activated successfully!!";
                }else{
                    $msg = "Status Updated successfully!!";
                }
                $result = Common_model::updateDataFromTable($table, $postData, 'id', $id);
            $res['error'] = 0;
            $res['msg'] = $msg;
        }else{
            $res['error'] = 1;
            $res['msg'] = 'This action has already performed';
        }
        echo json_encode($res);
    }
    
    public function unlinktheFile(Request $request)
    {
        $postData = array();
        $postData = $request->post();
        $table = $postData['table'];
        $id = $postData['id'];
        $docs = Common_model::getDataFromTable($table = $table, $field = '*',  $whereField = ['id' => $id], $whereValue = '', $orderBy = '', $order = 'ASC', $limit = '', $offset = 0, $resultInArray = true, $groupBy = '');
        $documentpath =  $docs[0]['document_path'];
        unlink($documentpath);
        $deleterow =  Common_model::deleteRowFromTable($table = $table, $field = ['id' => $id], $ID = 0, $limit = 0);
        if ($deleterow) {
            return true;
        } else {
            return false;
        }
    }
    
    public function ImageUpload(Request $request){
        try{
            $filetype   = $request->post('filetype');
            $image      = $request->file('file');
            
            if (!$image) {
                $res['error'] = 1;
                $res['msg'] = 'No file uploaded';
                return response()->json($res);
            }
            
            $extension  = $image->extension();
            $required_ext = ['jpg','jpeg','png','gif','svg'];
            if(isset($filetype) && $filetype!='' && $filetype!='undefined'){                
                if($filetype=='image' && !in_array($extension,$required_ext)){
                    $res['error'] = 1;
                    $res['msg'] = 'Invalid file format, accepts formats like .jpg,.jpeg,.png,.gif,.svg only';
                    return response()->json($res);
                }
            }
            // Manual validation to avoid exception handling issues
            $validator = \Validator::make($request->all(), [
                'file' => 'required|mimes:jpg,jpeg,png,gif,svg|max:10240' // 10MB max
            ]);
            
            if ($validator->fails()) {
                \Log::error('Image upload validation failed', [
                    'errors' => $validator->errors()->all()
                ]);
                $res['error'] = 1;
                $res['msg'] = 'Validation failed: ' . implode(', ', $validator->errors()->all());
                return response()->json($res, 422);
            }
            
            $destinationPath = $request->post('datapath', 'public/uploads/');
            
            // Ensure path ends with slash
            if (substr($destinationPath, -1) !== '/') {
                $destinationPath .= '/';
            }
            
            // Ensure directory exists
            if (!file_exists($destinationPath)) {
                if (!mkdir($destinationPath, 0755, true)) {
                    $res['error'] = 1;
                    $res['msg'] = 'Failed to create upload directory';
                    return response()->json($res);
                }
            }
            
            $input['imagename'] = time().'_'.uniqid().'.'.$image->extension();
            
            if($image->move($destinationPath, $input['imagename'])){
                $res['error'] = 0;
                $res['imageName'] = $input['imagename'];
                $filename = $input['imagename'];
                
                // Build URL dynamically - keep 'public/' in path since it's part of the URL structure
                // The file is stored in public/uploads/blog/, and the URL should reflect this
                $urlPath = $destinationPath . $filename;
                // Use url() helper to generate dynamic URL based on APP_URL from .env
                // This ensures URLs work across different environments (localhost, staging, production)
                $url = url($urlPath);
                
                // For TinyMCE compatibility, return both 'url' and 'location' fields
                // Some TinyMCE versions expect 'location' instead of 'url'
                $res['url'] = (string)$url;
                $res['location'] = (string)$url; // Alternative field name for TinyMCE compatibility
                $res['uploadedFile'] = "<small><a href='javascript:void(0)' onclick=openimage('".$url."') finalfile=".$filename.">View</a></small>";
                
                \Log::info('Image uploaded successfully', [
                    'filename' => $filename,
                    'url' => $url,
                    'path' => $destinationPath
                ]);
                
                return response()->json($res);
            } else {
                $res['error'] = 1;
                $res['msg'] = 'Failed to upload image';
                \Log::error('Image upload failed - move() returned false', [
                    'destination' => $destinationPath,
                    'filename' => $input['imagename']
                ]);
                return response()->json($res);
            }
        }catch(\Exception $e){
            \Log::error($e);
            $res['error'] = 1;
            $res['msg'] = $e->getMessage();
            return response()->json($res);
        }
    }
    
    public function docUpload(Request $request){
        try{
            $filetype      = $request->post('filetype');
            $image      = $request->file('file');
            $extension = $image->extension();
            $required_ext = ['doc','xlsx','xls','pdf'];
            if(isset($filetype) && $filetype!='' && $filetype!='undefined'){                
                if($filetype=='file' && !in_array($extension,$required_ext)){
                    $res['error'] = 1;
                    $res['msg'] = 'Invalid file format, accepts formats like .doc,.xlsx,.xls,.pdf only';
                    echo json_encode($res); exit;
                }
            }
            $validated = $this->validate($request,[
                'file' =>'required|mimes:doc,xlsx,xls,pdf'
            ]);
            
            $destinationPath = $_POST['datapath'];
            $input['imagename'] = time().'.'.$image->extension();
            if($image->move($destinationPath, $input['imagename'])){
                $res['error'] = 0;
                $res['imageName'] = $input['imagename'];
                $filename = $input['imagename'];;
                $url = env('APP_URL').$destinationPath.$filename;
                $res['uploadedFile'] = "<a href='$url' target='_blank' finalfile=".$filename.">View</a>";
            }
        }catch(\Exception $e){
            \Log::error($e);
            $res['error'] = 1;
            $res['msg'] = $e->getMessage();
        }
        echo json_encode($res);
    }
    
    public function griddesign(){
        $pageTitle = 'New Grid Design';
        return view('gridviews.griddesign', compact('pageTitle'));
    }
    
    public function viewnotification($param1='',$param2='',$param3='')
    {

        $data['updated_by'] = Auth::id(); // Use tbl_user.id (primary key)
        $data['updated_on'] = current_datetime();
        $data['status'] = NOTIFYREAD;
        $update_notification = Common_model::updateDataFromTable('tbl_notifications',$data,'id',$param2);
        $id = Crypt::encrypt($param3);
        try{
            switch($param1)
            {
                case 'Proposal':
                    $redirect = Redirect::to('view-proposal/'.$id);  
                break;
    
                case 'Lead':
                    $redirect = Redirect::to('/lead-management/view_lead/'.$id); 
                break;
    
                case 'Leave':
                    $redirect = Redirect::to('view_leave/'.$id);  
                break;
    
                case 'Task':
                    $redirect = Redirect::to('view-task-details/'.$id);  
                break;
    
                case 'Larvicide':
                    $redirect = Redirect::to('view-larvicide/'.$id);  
                break;
    
                case 'Stock_Transfer':
                    $redirect = Redirect::to('view-stock-transfer-details/'.$id);   
                break;
    
                default :
                    $redirect = Redirect::to('getallNotifications'); 
                break;
            }
            return $redirect;
        }catch(\Exception $e)
        {
            Log::error($e);
            return redirect()->back()->with('error','Unable to redirect');
        }
        
    }
    
    public function delete_record(Request $request)
    {
        $postData = [];
        $requestData = $request->getContent();
        $postData = json_decode($requestData, true); // Decode the JSON data
        $table = $postData['table'];
        if (!$this->hasTablePermission($table)) {
            return response()->json([
                'error' => 1,
                'msg' => 'Permission missing. Contact administrator.'
            ]);
        }
        $field = 'is_delete';
        $is_delete = $postData['deleteStatus'];
        $remarks = $postData['remarks'];
        $postData['is_delete'] = $postData['deleteStatus'];
        $postData['delete_comments'] = $remarks;
        $postData['updated_by'] = Auth::id(); // Use tbl_user.id (primary key)
        $postData['updated_on'] = current_datetime();
        $id = Crypt::decrypt($postData['id']);
        unset($postData['token']);
        unset($postData['id']);
        unset($postData['table']);
        unset($postData['deleteStatus']);
        unset($postData['remarks']);
        $where['id'] = $id;
        $where['is_delete'] = $postData['is_delete'];
        
        $isExist = Common_model::check_exists($table, $where, '', 'id', array());
        if($isExist == 0){
            if ($is_delete == 1){
                    $msg = "Record deleted successfully!!";
            }else if($is_delete == 0){ 
                $msg = "Deleted record status changed successfully!!";
            }else{
                $msg = "Deleted record status changed successfully!!";
            }
            $result = Common_model::updateDataFromTable($table, $postData, 'id', $id);
            $res['error'] = 0;
            $res['msg'] = $msg;
        }else{
            $res['error'] = 1;
            $res['msg'] = 'This action has already performed';
        }
        echo json_encode($res);
    }

    public function getstatesBycountryid(Request $request){
        try{
            $postData = [];
            $requestData = $request->post();
            $postData = json_decode($requestData['data'],1);
            $countryId = $postData['countryId'];
            $states = Common_model::getDataFromTable($table = 'tbl_states', $field = ['id','state_name'],  $whereField = ['status' => ACTIVE,'country_id'=>$countryId], $whereValue = '', $orderBy = 'id', $order = 'ASC', $limit = '', $offset = 0, $resultInArray = true, $groupBy = '');
            $state_data = "<option value=''>Select State</option>";
            if(is_array($states) && count($states)>0){
                foreach($states as $state){
                    $state_data.= "<option value='".$state['id']."'>".$state['state_name']."</option>";
                }
            }
            
            $data['error'] = 0;
            $data['states'] = $state_data;
            echo json_encode($data);exit;
        }catch(\Exception $e){
            \Log::error($e);
        }
    }

    private function hasTablePermission($table)
    {
        $tablePermissionMap = [
            'tbl_user' => 'users',
            'tbl_roles' => 'roles',
            'tbl_email_templates' => 'email_templates',
            'tbl_delay_categories' => 'delay_categories',
            'tbl_projects' => 'projects',
            'tbl_delay_registers' => 'delay_registers',
            'tbl_delay_mitigations' => 'mitigations',
            'tbl_delay_financial_impacts' => 'financial_impacts',
            'tbl_delay_attachments' => 'delay_attachments',
            'tbl_renovation_projects' => 'renovation_projects',
            'tbl_renovation_tasks' => 'renovation_projects',
            'tbl_renovation_daily_delay_logs' => 'renovation_projects',
        ];

        $permissionKey = $tablePermissionMap[$table] ?? '';
        if ($permissionKey === '') {
            return false;
        }

        return modulePermissionExists($permissionKey);
    }

}