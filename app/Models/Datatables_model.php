<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Datatables_model extends Model
{
    // use HasFactory;
    /**
    * @param array,array,string,array,array,string,string,string,string,string,string,string,string
    * @return object
    */
    public static function getDataTableResult($selectColumns, $dataTableSortOrdering, $table_name, $joinsArray, $wherecondition, $indexColumn = '', $searchColumns = '', $search_param = '', $orderByColumn = '', $sortType = '', $groupBy = '', $distinct = '', $includeJoinInCountQuery = '',$display_query='',$nullConditions=false)
    {
        
        $orders = DB::table($table_name);
        //echo $selectColumns;die;
        $orders->select($selectColumns);
        // Join Query
        if (!empty($joinsArray) && sizeof($joinsArray) > 0) {
            // if(!empty($joinsArray)){
            foreach ($joinsArray as $each) {
                $conditionArray = explode('=', $each['condition']);
                if ($each['join_type'] == 'inner' || empty($each['join_type'])) {
                    $orders->join($each['table_name'], $conditionArray[0], '=', $conditionArray[1]);
                } else {
                    $joinType = $each['join_type'] . 'Join';
                    $orders->$joinType($each['table_name'], $conditionArray[0], '=', $conditionArray[1]);
                }
            }
        }
        //print_r($wherecondition);
        if (!empty($wherecondition)) {
            //$orders->where($wherecondition);
            $count_where = "WHERE 1=1 ";
            
            foreach ($wherecondition as $eachcon) {

                if(!is_array($eachcon['value']) && stripos($eachcon['value'],'IS NOT NULL') > -1)
                {
                    $orders->whereNotNull($eachcon['column']);
                    $count_where .= ' AND '.$eachcon['column'].' '.$eachcon['value'];
                    continue;
                }
                
                if(!is_array($eachcon['value']) && stripos($eachcon['value'],'IS NULL') > -1)
                {
                    $orders->whereNull($eachcon['column']);
                    $count_where .= ' AND '.$eachcon['column'].' '.$eachcon['value'];
                    continue;
                }
                $column = $eachcon['column'];
                $operator = $eachcon['operator'] ? $eachcon['operator'] : '=';
                $value = $eachcon['value'];
                $condition = $eachcon['condition'];
                $cast = isset($eachcon['cast']) ? $eachcon['cast'] : null;
                if ($cast) {
                    $column = DB::raw("CAST($column AS $cast)");
                }
                
                if ($condition == 'and' && $value != '') {
                    $orders->where($column, $operator, $value);
                    if (is_numeric($value)) {
                        $count_where .= ' ' . $condition . ' ' . $column . $operator . $value;
                    } else {
                        $count_where .= ' ' . $condition . ' ' . $column . $operator . "'$value'";
                    }
                } elseif ($condition == 'in') {
                    $operator = "AND";
                    $orders->whereIn($column, $value);
                    // print_r($column); 
                    $in_value = implode("','", $value);
                    $in_value_string = "('" . $in_value . "')";
                    $count_where .= ' ' . $operator . ' ' . $column . ' ' . $condition . $in_value_string;
                    // print_r($value); exit;
                    // foreach($column as $key=>$val){
                    // $count_where .= ' ' . $column . ' ' . $condition . $value . $operator;
                    // echo $val;
                    // }
                    // $count_where .= ' ' . $column . ' ' . $condition . $value . $operator;
                } elseif ($condition == 'concat') {
                    $orders->whereRaw('1=1 ' . $operator . " " . " CONCAT(',', " . $column . ", ',') LIKE '%," . $value . ",%'");
                    $count_where .= ' ' . $operator . ' ' . " CONCAT(',', " . $column . ", ',') LIKE '%," . $value . ",%'";
                } elseif($condition == 'between'){
                    if(is_array($column)){
                        $orders->whereRaw('(' . $column[0] . " BETWEEN '" . $value[0] . "' AND '" . $value[1] . "' OR " . $column[1] . " BETWEEN '" . $value[0] . "' AND '" . $value[1] . "')");
                        $count_where .= ' AND (' . $column[0] . " BETWEEN '" . $value[0] . "' AND '" . $value[1] . "' OR " . $column[1] . " BETWEEN '" . $value[0] . "' AND '" . $value[1] . "')";
                    }else{
                        $orders->whereRaw($column .' BETWEEN '."'".$value[0]."'".' AND '."'".$value[1]."'");
                        $count_where .= ' AND '.$column .' BETWEEN '."'".$value[0]."'".' AND '."'".$value[1]."'";
                    }
                } elseif($condition == 'raw'){
                    // Support for raw SQL WHERE clauses with bindings
                    $rawSql = isset($eachcon['raw']) ? $eachcon['raw'] : $value;
                    $bindings = isset($eachcon['bindings']) ? $eachcon['bindings'] : [];
                    
                    if (!empty($bindings) && is_array($bindings)) {
                        $orders->whereRaw($rawSql, $bindings);
                        // For count query, replace placeholders with values (simple approach)
                        $countSql = $rawSql;
                        foreach ($bindings as $binding) {
                            $countSql = preg_replace('/\?/', is_numeric($binding) ? $binding : "'$binding'", $countSql, 1);
                        }
                        $count_where .= ' AND ' . $countSql;
                    } else {
                        $orders->whereRaw($rawSql);
                        $count_where .= ' AND ' . $rawSql;
                    }
                } 
                else {
                    $orders->where($column, $operator, $value);
                    $count_where .= ' ' . $condition . ' ' . $column . $operator . $value;
                }
            }
        }
        //print_r($wherecondition);exit;
        //echo $count_where;die();
        // searching Query
        if ($_POST) {
            if (isset($_POST['search']['value'])) {
                $search = $_POST['search']['value'];
                if (!empty($search)) {
                    $orders->where(function ($orders) use ($selectColumns, $search) {
                        foreach ($selectColumns as $eachColumn) {
                            $tableclmn = explode(' as ', $eachColumn);
                            $eachColumn = $tableclmn[0];
                            $orders->orWhere($eachColumn, 'LIKE', "%$search%");
                        }
                    });
                }
            }
        }


        if ($nullConditions) {
            foreach ($nullConditions as $column => $condition) {
                // echo 'column'.$column;
                // echo 'condition'.$condition;
                if ($condition === 'IS NULL') {
                    $orders->whereNull($column);
                } elseif ($condition === 'IS NOT NULL') {
                    $orders->whereNotNull($column);
                }
            }
        }
        
        // Custom Searching
        $filter = '';
        if (!empty($searchColumns)) {
            $search = $search_param;
            $sortFilters = array();
            if (!empty($search)) {
                $orders->where(function ($orders) use ($searchColumns, $search, &$sortFilters) {
                    foreach ($searchColumns as $eachColumn) {
                        $tableclmn = explode(' as ', $eachColumn);
                        $eachColumn = $tableclmn[0];
                        $orders->orWhere($eachColumn, 'LIKE', "%$search%");
                        $sortFilters[] = $eachColumn . " LIKE '%" . $search . "%'";
                    }
                });
                $searchCond = implode(' or ', $sortFilters);
            }
        }
        
        // echo 'filters - '.$filter;
        // print_r($searchColumns);
        // echo 'or condition - '.$searchCond;
        //print_r($_POST['order'][0]);
        //echo $_POST['order'][0]['column'];die();
        //print_r($dataTableSortOrdering);die();
        
        // Sorting Query
        
        if (!empty($groupBy)) {
            $orders->groupBy($groupBy);
        }

        if (isset($_POST['order']) && !empty($_POST['order'][0]) && $_POST['order'][0]['column'] != 0) 
        {
            $columnIndex = $_POST['order'][0]['column'];
            @$orderByColumn = $dataTableSortOrdering[$columnIndex];
            if ($orderByColumn == '') {
                $orderByColumn = $indexColumn;
            }
            $sortType = $_POST['order'][0]['dir'];
            //echo $sortType; exit;
            $orders->orderBy($orderByColumn, $sortType)->get();
            // $orders->orderBy($orderByColumn, $sortType)->get();
        } elseif ($orderByColumn != '' && $sortType != '') {
            $orders->orderBy($orderByColumn, $sortType)->get();
            // $orders->orderBy($orderByColumn, $sortType)->get();
        }
        if (isset($_POST['length']) && $_POST['length'] != -1) {
            //echo 'start'.$_POST['start'];
            //echo 'Length'.$_POST['length'];die();
            $orders->offset($_POST['start'])->limit($_POST['length']);
        }
        
        // Log the query before execution
        \Log::info('Datatables_model Query Details:', [
            'sql' => $orders->toSql(),
            'bindings' => $orders->getBindings(),
            'start' => $_POST['start'] ?? 'not_set',
            'length' => $_POST['length'] ?? 'not_set',
            'has_offset_limit' => isset($_POST['length']) && $_POST['length'] != -1
        ]);
        
        if($display_query){
            echo '<pre>';
            echo $orders->toSql(); 
            echo '<pre>';
            DB::enableQueryLog();
        }
        
        // Enable query log to capture actual executed queries
        DB::enableQueryLog();
        $result = $orders->get();
        
        // Log the actual executed queries
        $queries = DB::getQueryLog();
        \Log::info('Datatables_model Executed Queries:', [
            'query_count' => count($queries),
            'queries' => $queries,
            'result_count' => count($result)
        ]);
        // echo $orders->toSql();die();
        // $query = DB::getQueryLog();
        // dd($query);exit;
        // echo 'Distinct'.$distinct;
        
        $countQuery = "select count($indexColumn) as count from $table_name ";
        
        //echo "<pre>";print_r($joinsArray);exit;
        // Include joins in count query when requested OR when joins exist.
        // Without this, search conditions on joined aliases (e.g. v.vehicle_number)
        // break count query and make filters look like they are not working.
        if ($includeJoinInCountQuery != '' || (!empty($joinsArray) && is_array($joinsArray))) {
            $joinCondition = '';
            if (!empty($joinsArray)) {
                foreach ($joinsArray as $eachJoin) {
                    $joinType = $eachJoin['join_type'];
                    $jointable_name = $eachJoin['table_name'];
                    $joincondition = $eachJoin['condition'];
                    $joinCondition .= $joinType . ' join ' . $jointable_name . ' on ' . $joincondition . ' ';
                }
                $countQuery .= $joinCondition;
            }
        }
        
        // Add nullConditions to count query if they exist
        if ($nullConditions) {
            $nullWhere = '';
            foreach ($nullConditions as $column => $condition) {
                if ($condition === 'IS NULL') {
                    $nullWhere .= ($nullWhere ? ' AND ' : ' WHERE ') . $column . ' IS NULL';
                } elseif ($condition === 'IS NOT NULL') {
                    $nullWhere .= ($nullWhere ? ' AND ' : ' WHERE ') . $column . ' IS NOT NULL';
                }
            }
            if ($nullWhere) {
                $countQuery .= $nullWhere;
            }
        }
        
        if (!empty($wherecondition) && isset($count_where)) {
            // Check if we already have a WHERE clause from nullConditions
            if (stripos($countQuery, 'WHERE') !== false) {
                // Remove "WHERE 1=1 " from count_where and add as AND conditions
                $count_where_clean = str_replace('WHERE 1=1 ', '', $count_where);
                $countQuery .= ' AND ' . ltrim($count_where_clean, ' AND ');
            } else {
                $countQuery .= " " . $count_where;
            }
        }

        
        
        if (!empty($searchCond)) {
            if (isset($count_where)) {
                $countQuery .= " and ($searchCond) ";
            } else {
                $countQuery .= "WHERE 1=1 and ($searchCond) ";
            }
        }
        if (!empty($groupBy)) {
            $countQuery .= " group by  $indexColumn";
        }
        // echo $countQuery;die();
        $res =  DB::select($countQuery);
        if (!empty($groupBy)) {
            $table_total = count($res);
        } else {
            $table_total = $res[0]->count;
        }
        
        //  echo 'table Count - '.$table_total;
        // exit;
        $getRecordListing = [];
        $getRecordListing['draw'] = 1;
        $getRecordListing['recordsTotal'] = $table_total;
        $getRecordListing['recordsFiltered'] = $table_total;
        $result = json_decode(json_encode($result));
        $getRecordListing['data'] = $result;
        //echo "<pre>";print_r($getRecordListing); exit;
        return $getRecordListing;
    }
    
    public static function getDataTableResultwithoutPagination($selectColumns, $dataTableSortOrdering, $table_name, $joinsArray, $wherecondition, $indexColumn = '', $orderByColumn = '', $sortType = '', $serachColumns = '', $searchValue = '', $groupBy = '', $whereinField = '', $whereinValue = '', $limit = 0, $offset = 0, $orwhere = '',$resultArray=false,$dis_qry=false,$nullConditions=false)
    {
        
        
        $data = DB::table($table_name);
        $data->select($selectColumns);
        if (!empty($joinsArray)) {
            foreach ($joinsArray as $each) {
                $conditionArray = explode('=', $each['condition']);
                if ($each['join_type'] == 'inner' || empty($each['join_type'])) {
                    $data->join($each['table_name'], $conditionArray[0], '=', $conditionArray[1]);
                } else {
                    $joinType = $each['join_type'] . 'Join';
                    $data->$joinType($each['table_name'], $conditionArray[0], '=', $conditionArray[1]);
                }
            }
        }
        if (!empty($wherecondition)) {
            foreach ($wherecondition as $key => $value) {
                if (stripos($key, "!")) {
                    $data->where(str_replace("!", "", $key), "!=", $value);
                }
                else if(stripos($key, ">")){
                    $data->where(str_replace(">", "", $key), ">", $value);
                    // echo 'key - >'.$key.' , '.$value;
                }else if(stripos($key, "<")){
                    $data->where(str_replace("<", "", $key), "<", $value);
                }else if (is_array($value) && count($value) === 2) {
                    // ✅ Auto-detect whereBetween if value is an array of two items
                    $data->whereBetween($key, $value);

                }else {
                    if (isset($value['column'])) { 
                        $column = $value['column'];
                        $condition = $value['condition'];
                        $cast = isset($value['cast']) ? $value['cast'] : null;
                        if ($cast) {
                            $column = DB::raw("CAST($column AS $cast)");
                        }
                        $data->where($column, $condition, $value['value']);
                    } else {
                        $data->where($key, $value);
                    }
                }
            }
        }       
        
        if (!empty($orwhere)) {
            //print_r($orwhere);	
            foreach ($orwhere as $key => $value) {
                //print_r($eachcol);die();
                $data->orWhere($key, $value);
            }
        }
        if (!empty($whereinValue)) {
            $data->whereIn($whereinField, $whereinValue);
        }
        if ($nullConditions) {
            foreach ($nullConditions as $column => $condition) {
                // echo 'column'.$column;
                // echo 'condition'.$condition;
                if ($condition === 'IS NULL') {
                    $data->whereNull($column);
                } elseif ($condition === 'IS NOT NULL') {
                    $data->whereNotNull($column);
                }
            }
        }
        if (!empty($searchValue)) {
            $search = stripslashes($searchValue);
            if (!empty($search) && is_array($serachColumns)) {
                $data->where(function ($query) use ($serachColumns, $search) {
                    foreach($serachColumns as $key=>$eachColumn){
                        $tableclmn = explode(' as ',$eachColumn);
                        $eachColumn = $tableclmn[0];
                        $query->orWhere($eachColumn, 'like', "%".$search."%");
                    }
                });
            }
        }
        
        if (!empty($orderByColumn) && !empty($sortType)) {
            
            if(is_array($orderByColumn) && is_array($sortType))
            {
                foreach ($orderByColumn as $index => $eachColumn) {
                    $data->orderBy($orderByColumn[$index],$sortType[$index]);
                }
            }
            else
            {
                $data->orderBy($orderByColumn, $sortType);
            }
        }
        if (!empty($groupBy)) {
            $data->groupBy($groupBy);
        }
        
        
        
        if ($limit > 0) {
            $data->offset($offset)->limit($limit);
        }
        if($dis_qry){
            echo $data->toSql();
        }
      //  echo $data->toSql();
        if($resultArray){
            $res = $data->get();
            $result = collect($res)->map(function ($x) {
                return (array) $x;
            })->toarray();
            return  $result;
        }else{
            return $result = $data->get();
        }
    }
    
    public static function getDataTableResultforAPIPagination($selectColumns, $dataTableSortOrdering, $table_name, $joinsArray, $wherecondition, $indexColumn = '', $orderByColum, $sortType, $limit, $ofset,$resultArray=false)
    {
        $orders = DB::table($table_name);
        $orders->select($selectColumns);
        // Join Query
        if (!empty($joinsArray) && sizeof($joinsArray) > 0) {
            // if(!empty($joinsArray)){
            foreach ($joinsArray as $each) {
                $conditionArray = explode('=', $each['condition']);
                if ($each['join_type'] == 'inner' || empty($each['join_type'])) {
                    $orders->join($each['table_name'], $conditionArray[0], '=', $conditionArray[1]);
                } else {
                    $joinType = $each['join_type'] . 'Join';
                    $orders->$joinType($each['table_name'], $conditionArray[0], '=', $conditionArray[1]);
                }
            }
        }
        
        if (!empty($wherecondition)) {
            foreach ($wherecondition as $key => $value) {
                if (stripos($key, "!")) {
                    $orders->where(str_replace("!", "", $key), "!=", $value);
                }else
                if(stripos($key, ">")){
                    $orders->where(str_replace(">", "", $key), ">", $value);
                    // echo 'key - >'.$key.' , '.$value;
                }elseif(stripos($key, "<")){
                    $orders->where(str_replace("<", "", $key), "<", $value);
                } 
                else {
                    $orders->where($key, $value);
                }
            }
        }
        // searching Query
        if ($_POST) {
            $search = $_POST['search']['value'];
            if (!empty($search)) {
                foreach ($selectColumns as $eachColumn) {
                    $tableclmn = explode(' as ', $eachColumn);
                    $eachColumn = $tableclmn[0];
                    $orders->orwhere($eachColumn, 'like', "'%$search%'");
                    //preg_replace('/\b[a-z]+\b/', '', Str::of($orders)->lower());
                }
            }
        }
        
        // Sorting Query
        if (!empty($_POST['order'][0]) && $_POST['order'][0]['column'] != 0) {
            $columnIndex = $_POST['order'][0]['column'];
            $orderByColumn = $dataTableSortOrdering[$columnIndex];
            // if($orderByColumn==''){
            //     $orderByColumn=$indexColumn;
            // }
            $sortType = $_POST['order'][0]['dir'];
            $orders->orderBy($orderByColumn, $sortType);
        } elseif ($orderByColum != '' && $sortType != '') {
            // $columnIndex=$indexColumn;
            //$orderByColumn=$dataTableSortOrdering[$columnIndex];
            //$sortType=$_POST['order'][0]['dir'];
            $orders->orderBy($orderByColum, $sortType);
        } else {
            // if($orderByColumn==''){
            //     $orderByColumn=$indexColumn;
            // }
            $orders->orderBy($orderByColumn, 'desc');
        }
        //  echo $orders->toSql();exit; 
        if($resultArray){
            $res = $orders->skip($ofset)->take($limit)->get();
            $result = collect($res)->map(function ($x) {
                return (array) $x;
            })->toarray();
            return  $result;
        }else{
            return $orders->skip($ofset)->take($limit)->get();
        }
        
        //  echo $orders->toSql(); 
        //  exit; 
    }
    
    public static function filterData($data, $searchValue = '', $status = '')
    {
        $dataarr = json_decode($data, true);
        //echo 'InSideModel';
        //print_r($dataarr);die();
        $finalArr = array();
        if ($searchValue != '') {
            $finalArr = array();
            $filteredData = array();
            foreach ($dataarr['data'] as $item) {
                foreach ($item as $value) {
                    if (str_contains($value, $searchValue)) {
                        $filteredData[] = $item;
                        break; // Stop checking other values for this item
                    }
                }
            }
        }
        
        if ($searchValue == '' && $status != '') {
            $filteredData = array();
            foreach ($dataarr['data'] as $item) {
                foreach ($item as $value) {
                    $value = trim($value);
                    //echo 'Value->>>>'.$value;
                    if ($value === $status) {
                        $filteredData[] = $item;
                        break; // Stop checking other values for this item
                    }
                }
            }
        }
        
        if ($searchValue != '' && $status != '') {
            $filteredData = array();
            foreach ($dataarr['data'] as $item) {
                foreach ($item as $value) {
                    if (str_contains($value, $searchValue) && trim($item['status']) == trim($status)) {
                        $filteredData[] = $item;
                        break; // Stop checking other values for this item
                    }
                }
            }
        }
        
        $recordsTotal = count($filteredData);
        $recordsFiltered = count($filteredData);
        //$dataWithoutNumericKeys = array_values($filteredData);
        $filteredData = json_encode($filteredData);
        $filteredArrobj = json_decode($filteredData);
        $finalArr = ['recordsTotal' => $recordsTotal, 'recordsFiltered' => $recordsFiltered, 'data' => $filteredArrobj];
        //echo 'InSidefilterFunction';
        //print_r($finalArr);die();
        return $finalArr;
    }
    
    public static function executeQuery($sql,$is_array=false)
    {
        /*echo 'Sqlwewe'.$sql;
        $res =  DB::select($sql);
        if($is_array){
        $result = collect($res)->map(function ($x) {
        return (array) $x;
        })->toarray();
        return  $result;
        }else{
        return $res;
        }*/
        
        try {
            // Determine if the SQL statement is of a type that returns a result set
            $is_select_query = preg_match('/^\s*(SELECT|WITH)\s/i', $sql);
            
            if ($is_select_query) {
                // Use DB::select() for SELECT queries
                $res = DB::select($sql);
            } else {
                // Use DB::statement() for non-SELECT queries
                $res = DB::statement($sql);
            }
            
            // Convert the result to an array if requested and the result is an array
            if ($is_array && $is_select_query) {
                $result = collect($res)->map(function ($x) {
                    return (array) $x;
                })->toArray();
                return $result;
            } else {
                return $res;
            }
        } catch (\PDOException $e) {
            // Handle exception
            return response()->json(['error' => $e->getMessage()], 500);
        }
        
    }
    
    public static function displayCommaSeparatedNames($table,$searchColumnIndex,$columName,$columnValue)
    {
        if($columnValue!=''){
            $sql = " select $columName as name from  $table  where Try_Cast($searchColumnIndex As varchar)  in ($columnValue) ";
            $res =  DB::select($sql);
            $result = collect($res)->map(function ($x) {
                return (array) $x;
            })->toarray();          
            
            if(!empty($result)){
                $tempArray = [];
                foreach($result as $each){
                    if(isset($each['name'])){
                        $tempArray[] = $each['name'];
                    }else{
                        $tempArray[] = '';
                    }
                }
                return $name = implode(', ',$tempArray);
            }   
        }
        return '';
    }
    
    public static function getColumnValueInCommaSeperated($table,$searchColumnIndex,$columnName,$columnValue,$flag = false){
        try{
            $sql = " select STRING_AGG($searchColumnIndex, ',') AS $searchColumnIndex  from  $table  where $columnName='$columnValue' group by $columnName";
            $res =  DB::select($sql);
            $result = collect($res)->map(function ($x) {
                return (array) $x;
            })->toarray();          
            if(is_array($result) && isset($result[0][$searchColumnIndex])){
                if($flag){
                    $values = SELF::displayCommaSeparatedNames('tbl_services','id','service_name',$result[0][$searchColumnIndex]);
                    return $values;
                }else{
                    return $result[0][$searchColumnIndex];
                }
            }
        }catch(QueryException $e){
            \Log::error($e);
            return false;
        }
    }
    
    public static function getDataTableResultforDropdown($selectColumns, $dataTableSortOrdering, $table_name, $joinsArray, $wherecondition, $indexColumn = '', $searchColumns = '', $search_param = '', $orderByColumn = '', $sortType = '', $groupBy = '', $distinct = '', $includeJoinInCountQuery = '', $limit = '', $offset = '', $whereinField = '', $whereinValue = [])
    {
        $orders = DB::table($table_name);
        $orders->select($selectColumns);
        // Join Query
        if (!empty($joinsArray) && sizeof($joinsArray) > 0) {
            // if(!empty($joinsArray)){
            foreach ($joinsArray as $each) {
                $conditionArray = explode('=', $each['condition']);
                if ($each['join_type'] == 'inner' || empty($each['join_type'])) {
                    $orders->join($each['table_name'], $conditionArray[0], '=', $conditionArray[1]);
                } else {
                    $joinType = $each['join_type'] . 'Join';
                    $orders->$joinType($each['table_name'], $conditionArray[0], '=', $conditionArray[1]);
                }
            }
        }
        //print_r($wherecondition);
        // if (!empty($wherecondition)) {
        //     $orders->where($wherecondition);
        // }
        if (!empty($wherecondition) && count($whereinValue) > 0) {
            $orders->where($wherecondition)->whereIn($whereinField, $whereinValue);
        } else if (!empty($wherecondition) && count($whereinValue) == 0) {
            $orders->where($wherecondition);
        } else if (empty($wherecondition)) {
            $orders->whereIn($whereinField, $whereinValue, FALSE);
        }
        // Custom Searching
        $filter = '';
        if (!empty($searchColumns)) {
            $search = $search_param;
            $sortFilters = array();
            if (!empty($search)) {
                $orders->where(function ($orders) use ($searchColumns, $search, &$sortFilters) {
                    foreach ($searchColumns as $eachColumn) {
                        $tableclmn = explode(' as ', $eachColumn);
                        $eachColumn = $tableclmn[0];
                        $orders->orWhere($eachColumn, 'LIKE', "%$search%");
                        $sortFilters[] = $eachColumn . " LIKE '%" . $search . "%'";
                    }
                });
            }
        }
        if (!empty($orderByColumn) && !empty($sortType)) {
            $orders->orderBy($orderByColumn, $sortType);
        }
        if (!empty($groupBy)) {
            $orders->groupBy($groupBy);
        }
        
        if ($limit > 0) {
            $orders->offset($offset)->limit($limit);
        }
        //    if(count($whereinValue)>0){
        //     print_r($wherecondition);
        //     print_r($whereinValue);
        //      echo $orders->toSql();exit; 
        //    }
        $result = $orders->get();
        // print_r($result);
        return $result;
    }
    
}
