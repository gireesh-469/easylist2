<?php
/**
 * @package EasyList2
 */
namespace EasyList2;

use Exception;
use PDO;
use PDOException;
use DateTime;
use EasyList2\Exceptions\EasyListException;

class Listing
{
    public static $connection;
    public static $protocol;
    
    /**
     * Creates Connection
     */
    public static function Connection()
    {
        if(!self::$connection){
            $conn = new ListConnection();
            self::$connection = $conn->setConnection();
            self::$protocol = $conn->getProtocol();
        } 
    }
    
    /**
     * @param array $options
     * $options array 
     * array(
         "select" 	             => "<Comma separated column list>"
        ,"from" 	             => "<From table with alias>"
        ,"joins" 	             => "<Join statements>"
        ,"conditions"            => array(
                        			array("condition" => "name = ?", "value" => "<FILTER-NAME>", "operation" => "AND" ),
                        			array("condition" => "age = ?", "value" => "<FILTER-AGE>", "operation" => "OR" ),
                        			array("condition" => "(name = ? AND age IN( ?) )", "value" => array(<FILTER-NAME>, <ARRAY-FILTER-AGE->)), "operation" => "OR" )
                        		  )
        ,"group" 	             => "<Comma separated group names>"
        ,"having" 	             => array(
                        			array("condition" => "name = ?", "value" => "<FILTER-NAME>", "operation" => "AND" ),
                        			array("condition" => "age = ?", "value" => "<FILTER-NAME>", "operation" => "OR" )
                        		  )
        ,"having_columns"        => "<Comma separated list of columns used in HAVING cluase. This is to prevent count query error when HAVING is used>"                         		  
                        		  //Note : Either filter or condition will be considered
       ,"filters"                 => array( 
                                    array("condition" => "alias.Column-name = ?", "form-field" => "<INPUT ELEMENT NAME OF FORM>", "operation" => "AND|OR", "type"=>"BOOLEAN|DATE|TIME|INTEGER|STRING", "datetime_format_from"=>"d/m/Y : Use php date format", "datetime_format_to"=>"d/m/Y", "consider_empty" => "YES|NO : Default - NO"),
                                    array("condition" => "alias.Column-name = ?", "form-field" => "<INPUT ELEMENT NAME OF FORM>", "operation" => "AND|OR", "type"=>"BOOLEAN|DATE|DATETIME|TIME|INTEGER|STRING", "datetime_format_from"=>"d/m/Y : Use php date format", "datetime_format_to"=>"PHP date format d/m/Y", "consider_empty" => "YES|NO : Default - NO"),
                                  )
        ,"order" 	             => "<Comma separated order coluns with ASC/DESC key >"
        ,"return_data"           => "<HTML / JSON / OBJECT / QUERY>"
        ,"view"	                 => "<view location if return_data is HTML>"
        ,"view_variables"        => array("variable"=>"$variableName" [...])
        ,"page" 	             => "<page number>"
        ,"pagination" 	         => "YES | NO - Default Yes"
        ,"page_size"             => "<page size>"
       )
     */
    public static function Page($options)
    {
        $sql                = "";
        $count_sql          = "";
        $select             = "";
        $query              = "";
        $viewData           = "";
        $subCondition       = "";
        $methodArray        = (!empty($_POST)) ? $_POST : $_GET;
        
        $return_data        = isset($options["return_data"]) ? $options["return_data"] : "JSON";
        $page_size          = isset($methodArray['page_size']) ? $methodArray['page_size'] : (isset($options["page_size"]) ? $options["page_size"] : 25);
        $page               = isset($methodArray['page']) ? $methodArray['page'] : (isset($options["page"]) ? $options["page"] : 1);
        $total_records      = isset($methodArray['total_records']) ? $methodArray['total_records'] : (isset($options["total_records"]) ? $options["page"] : 0);
        $pagination         = isset($options["pagination"]) ? $options["pagination"] : 'YES';
        $having_columns     = isset($options["having_columns"]) ? "," . trim($options["having_columns"],",") : '';
        
        $order              = isset($options["order"]) ? $options["order"] : "";
        $sort               = isset($methodArray['sort']) ? $methodArray['sort'] : "";
        $sort_type          = isset($methodArray['sort_type']) ? $methodArray['sort_type'] : "";
        
        $mainData = array(
             "page_size"       => $page_size
            ,"page"            => $page
            ,"total_records"   => $total_records
            ,"next_page"       => 1
            ,"prev_page"       => 1
            ,"last_page"       => 1
            ,"total_pages"     => 1 
            ,"return_data"     => $return_data
            ,"data"            => array()
        );
        
        $data = array();
        
        if(!isset($options['select']) || trim($options['select']) == "" || !isset($options['from']) || trim($options['from']) == "" ){
            throw new EasyListException("Select OR From clause is missing.");
        } else {
            $select = "SELECT " . $options['select'];
            $sql .= " FROM " . $options['from'];
        }
        
        if(isset($options['joins']) && $options['joins'] !=""){
            $sql .= $options['joins'];
        }
        
        //Condtion option will not consider if Filter option is present
        if(isset($options['filters']) && is_array($options['filters'])){
            $subCondition = self::ConditionBuilderForFilter($options['filters']);
            if(trim($subCondition) != ""){
                $sql .= " WHERE " .  $subCondition;
            }
        } elseif(isset($options['conditions']) && $options['conditions'] !=""){
            $subCondition = self::ConditionBuilder($options['conditions']);
            if(trim($subCondition) != ""){
                $sql .= " WHERE " .  $subCondition;
            }
        }
        
        if(isset($options['group']) && $options['group'] !=""){
            $sql .= " GROUP BY " .  $options['group'];
        }
        
        if(isset($options['having']) && $options['having'] !=""){
            $subCondition = self::ConditionBuilder($options['having']);
            $sql .=  " HAVING " . $subCondition;
        }
        
        //Count query - No order clause requred
        $count_sql = $sql;
        
        if($sort != ""){
            $sql .=  " ORDER BY " . self::Decode($sort) . " $sort_type ";
        } elseif($order != ""){
            $sql .=  " ORDER BY " . $options['order'];
        }

        if($return_data != "QUERY"){
            self::Connection();
            
            //Start : Pagination section 
            if($pagination == "YES"){
                if($total_records == 0){
                    try{
                        $stmt = self::$connection->prepare("SELECT COUNT(*) AS count {$having_columns} FROM (SELECT 1 " . $count_sql . ") AS query");
                        $stmt->execute();
                        $rec = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        $mainData["total_records"] = $total_records = ($rec["count"]) ? $rec["count"] : 0;

                    }catch(Exception $e){
                        throw new EasyListException("Error in count query : " . $e->getMessage());
                    }
                }
                
                $total_pages = intval(ceil($total_records / $page_size));
                if($page >= $total_pages){
                    $page = $total_pages;
                }
                
                $next_page = ($page === $total_pages) ? $page : $page + 1;
                $prev_page = ($page == 1) ? 1 : $page - 1;
                $offset    = ($page - 1) * $page_size;
                
                $mainData["next_page"] = $next_page;
                $mainData["prev_page"] = $prev_page;
                $mainData["last_page"] = $total_pages;
                $mainData["total_pages"] = $total_pages;
                
                if($total_pages > 0){
                    
                    switch(self::$protocol){
                        case 'MYSQL':
                            $sql .= " LIMIT {$offset},{$page_size} ";
                            break;
                        case 'SQLSRV':
                            $sql .= " LIMIT {$page_size} OFFSET {$offset} ";
                            break;
                        case 'ORACLE':
                            if($offset <= 0){
                                $sql .= " FETCH NEXT {$page_size} ROWS ONLY ";
                            } else {
                                $sql .= " OFFSET {$offset} ROWS FETCH NEXT {$page_size} ROWS ONLY ";
                            }
                            break;
                        case 'POSTGRESQL':
                            $sql .= " OFFSET {$offset} LIMIT {$page_size} ";
                            break;
                        case 'SYBASE':
                            $sql .= " LIMIT {$page_size} OFFSET {$offset} ";
                            break;
                        case 'INFORMIX':
                            //:TODO
                            break;
                        default:
                            break;
                    }
                    
                    
                    
                    $sql .= " LIMIT {$offset},{$page_size}";
                }
            }
            //End : Pagination section
            
            try{
                $stmt = self::$connection->prepare($select . $sql);
                $stmt->execute();
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }catch(Exception $e){
                throw new EasyListException("Error in the query : " . $e->getMessage());
            }
        }
        
        //Handling return data
        switch($return_data){
            case 'HTML' :
                if(isset($options["view"]) && $options["view"] != ""){
                    ob_start();
                    require $options["view"];
                    $viewData = ob_get_clean();
                } else {
                    $viewData = "";
                }
                break;

            case 'JSON' :
            case 'OBJECT' :
                $viewData = $data;
                break;

            case 'QUERY' :
                unset($mainData["page_size"]);
                unset($mainData["page"]);
                unset($mainData["total_records"]);
                unset($mainData["return_data"]);
                unset($mainData["data"]);
                
                $mainData["query"] = $select . $sql;
                $mainData["count_query"] = "SELECT COUNT(*) AS count  {$having_columns} FROM (SELECT 1 " . $sql . ") AS query";
                $viewData = "";
                break;
        }
    
        $mainData["data"] = $viewData;

        if($return_data == "OBJECT"){
            $mainData = (object) $mainData;
        } else {
            $mainData = json_encode($mainData,  JSON_INVALID_UTF8_IGNORE |  JSON_PARTIAL_OUTPUT_ON_ERROR);
        }
        
        self::$connection = null;
        
        return $mainData;
    }
    
    /**
     * @param array $condition
     * @return string
     * Description : Prepare condtions by including values 
     */
    public static function ConditionBuilder($condition){
        $result = "";
        
        foreach($condition AS $eachCondition){
            $subCondition = "";
            $subValues = "";
            
            $clean_condition = self::CeanQuotes($eachCondition['condition']);
            $ary_subCondition = explode("?", $clean_condition);
            
            for($i = 0; $i < count($ary_subCondition); $i++){
                if($i == 0) {continue;}
                
                if(is_array($eachCondition['value'])){
                    if(is_array($eachCondition['value'][$i-1])){
                        $subValues = "'" . implode("','", $eachCondition['value'][$i-1]) . "'";
                        $subCondition .= $subValues;
                    } else {
                        $subCondition .= "'" . $eachCondition['value'][$i-1] . "'";
                    }
                } else {
                    $subCondition .= "'" . $eachCondition['value'] . "'";
                }
                
                $subCondition .= " " . $ary_subCondition[$i];
            }
            
            $result .= $ary_subCondition[0] . $subCondition . " " . $eachCondition["operation"] . " ";
        }
        
        $result = trim($result, 'AND ');
        $result = trim($result, 'OR ');
        
        return $result;
    }
    
    /**
     * @param String $condition
     * @return String
     * Description : Remove quotes before question marks
     */
    public static function CeanQuotes($condition){
        $result = $condition;
        
        $pattern = "/'\s*\?\s*'/i";
        $result = preg_replace($pattern, '?', $result);
        $pattern = '/"\s*\?\s*"/i';
        $result = preg_replace($pattern, '?', $result);
        
        return $result;
    }
    
    /**
     * @param array $filter
     * @return string
     * Description : Prepare filter based on the conditions 
     */
    public static function ConditionBuilderForFilter($filter){
    
        $result      = "";
        $methodArray = (!empty($_POST)) ? $_POST : $_GET;
        $listFilter = new ListFilter();
        
        foreach($filter AS $eachfilter){
            $subfilter = "";
            $complextype = isset($eachfilter['type']) ? trim(strtoupper($eachfilter['type'])) : "";
            
            if($complextype == "COMPLEX"){
                foreach($eachfilter['condition'] AS $subEachFilter){
                    $subfilter .= $listFilter->filter($subEachFilter, $methodArray);
                }
            } else {
                $subfilter = $listFilter->filter($eachfilter, $methodArray);
            }
            
            $result .= $subfilter;
        }
        
        $result = trim($result, 'AND ');
        $result = trim($result, 'OR ');

        return $result;
    }
    
    /**
     * 
     * @param String $string
     * @return String
     * Description : Encode to base64
     */
    public static function Encode($string)
    {
        return base64_encode($string);
    }
    
    
    /**
     * 
     * @param String $string
     * @return String
     * Description : Decode String from base64
     */
    public static function Decode($string)
    {
        return base64_decode($string);
    }
    
    /**
     * @param array $config
     * * array(
        "url"            => "<target location of controller/action function>",
        'form_id'        => '<form id >',
        'target_div_id'  => '<div id where we want to show the output>',
        'button_id'      => '<filter button id>',
        "autolist"       => <true | false : If true will show the output first time without pressing button>,
        "column"         => array( //Provide header detail
                                array("head" => "Code", "column" => "a_code", "width" => '30%',"sort" => "a_code", "class" => "text-center"),
                                array("head" => "Town", "column" => "a_town", "width" => '30%', "class" => "","sort" => "a_town"),
                                array("head" => "Address", "column" => "a_state", "width" => '40%', "class" => "","sort" => "address")
                           ),
        "pager"          => "BOTH", //Location where we want to show the page controller 
                            //Action colum where we can add edit/delete buttons 
        "action"         => array("<a href='http://www.test.com/{a_country}/{name}/index'><span class='glyphicon glyphicon-pencil'></span></a>&nbsp;",
                                "&nbsp;<a href='http://www.test.com/{id}/{name}/delete'><i style='color:red;font-size:20px;' class='fa fa-trash-o'></i></a>&nbsp;",
                                '<a href="http://www.test.com/{xx}/view"><span style="font-size:20px;" class="fa fa-eye"></span></a>'
                           )
       )
     */
    public static function List($config)
    {
        if(!array_key_exists("url", $config) || !array_key_exists("form_id", $config) || !array_key_exists("target_div_id", $config) ||
            !array_key_exists("button_id", $config) || !array_key_exists("column", $config) ||!array_key_exists("pager", $config))
        {
              throw new EasyListException("Required parameters missing. Check these parameter : url, form_id, target_div_id, button_id, column, pager.");
        }
        
        if(!array_key_exists($config['column'], $config)){
            $config['column'] = array_map(function($obj){ $obj['sort'] = base64_encode(trim($obj['sort'])); return $obj; }, $config['column']);
        }
        
        $config = rawurlencode(str_replace('null', '""', json_encode($config)));
        
        echo '<input type="hidden" id="easylist-config" value=\''.$config.'\' />';
    }
    
    /**
     * @param array $page
     * Description : Provide pager widget to developter to design his own control
     */
    public static function Pager($page, $formid = null,$page_sizes = null)
    {
        $start_page = $page->total_records == 0 ? 0 : 1;
        $min = ($page->page - 1) * $page->page_size + $start_page;
        $max = $min + $page->total_pages - $start_page;
        $sizeOptions = "";
        
        if($page_sizes == null){
            $page_sizes = array(10,25,50,100,250);
        }
        
        foreach($page_sizes as $eachSize){
            $selected = ($eachSize ==  $page->page_size) ? "selected" : "";
            $sizeOptions .= "<option value='{$eachSize}' {$selected}>{$eachSize}</option>";
        }
        $random = rand (10000, 99999);
        $html = "<div class='custom-pagination'>
					<a href='javascript:void(0)' class='first-page enabled' title='First' data-page='1' onclick='pagination{$random}(1,this,{$page->page_size},{$page->total_records})'>
      					<span class='glyphicon glyphicon-step-backward'></span>
    				</a>
    				<a href='javascript:void(0)' class='prev-page enabled' title='Previous' data-page='{$page->prev_page}' onclick='pagination{$random}({$page->prev_page},this,{$page->page_size},{$page->total_records})'>
      					<span class='glyphicon glyphicon-backward'></span>
    				</a>
    				<div class='pagedisplay'>
      					Records {$min} to {$max} (Total {$page->total_records} Results) - Page {$page->page} of {$page->total_pages}
    				</div>
    				<a href='javascript:void(0)' class='next-page enabled' title='Next' data-page='{$page->next_page}' onclick='pagination{$random}({$page->next_page},this,{$page->page_size},{$page->total_records})'>
      					<span class='glyphicon glyphicon-forward'></span>
    				</a>
    				<a href='javascript:void(0)' class='last-page enabled' title='Last' data-page='{$page->last_page}' onclick='pagination{$random}({$page->last_page},this,{$page->page_size},{$page->total_records})'>
      					<span class='glyphicon glyphicon-step-forward'></span>
    				</a>
					<select class='page-limit' onchange=paginationBySize{$random}({$page->page},this,{$page->total_records})>{$sizeOptions}</select>
  				</div>
                <script>
                    function pagination{$random}(page, element, page_size, total_records){
                        //var form_id = element.closest('form').id;
                        form_id = '{$formid}';
                        updateHiddenAttribute{$random}('page', page, form_id);
                        updateHiddenAttribute{$random}('page_size', page_size, form_id);
                        updateHiddenAttribute{$random}('total_records', total_records, form_id);
                        document.getElementById(form_id).submit();
                    }
                    function paginationBySize{$random}(page, element,total_records){
                        //var form_id = element.closest('form').id;
                        form_id = '{$formid}';
                        var page_size = element.value;
                        updateHiddenAttribute{$random}('page', page, form_id);
                        updateHiddenAttribute{$random}('page_size', page_size, form_id);
                        updateHiddenAttribute{$random}('total_records', total_records, form_id);
                        document.getElementById(form_id).submit();
                    }
                    function updateHiddenAttribute{$random}(name, value, form){
                        if(document.getElementById(form).elements[name]){
                            document.getElementById(form_id).elements[name].value = value;
                        }else{
                            //addHiddenField{$random}(name, value, form);
                            var input = document.createElement('input');
                            input.setAttribute('type', 'hidden');
                            input.setAttribute('name', name);
                            input.setAttribute('value', value);
                            //append to form element that you want .
                            document.getElementById(form).appendChild(input);
                        }
                    }
                </script>";
        echo $html;
    }
    
}
