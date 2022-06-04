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

class DynaList
{
    public static $connection;
    
    /**
     * Creates Connection
     */
    public static function Connection()
    {
        if(!self::$connection){
            $conn = new ListConnection();
            self::$connection = $conn->setConnection();
        } 
    }
    
    /**
     * @param array $options
     * $options array : select, joins, from, conditions, group, having, limit, offset, order 
     * array(
         "select" 	=> "<Comma separated column list>"
        ,"from" 	=> "<From table with alias>"
        ,"joins" 	=> "<Join statements>"
        ,"conditions" => array(
        			array("condition" => "name = ?", "value" => "<FILTER-NAME>", "operation" => "AND" ),
        			array("condition" => "age = ?", "value" => "<FILTER-AGE>", "operation" => "OR" ),
        			array("condition" => "(name = ? AND age IN( ?) )", "value" => array(<FILTER-NAME>, <ARRAY-FILTER-AGE->)), "operation" => "OR" )
        		)
        ,"group" 	=> "<Comma separated group names>"
        ,"having" 	=> array(
        			array("condition" => "name = ?", "value" => "<FILTER-NAME>", "operation" => "AND" ),
        			array("condition" => "age = ?", "value" => "<FILTER-NAME>", "operation" => "OR" )
        		)
       ,"filter" => array( //Either filter of condition will be condier
            array("condition" => "alias.Column-name = ?", "form-field" => "<INPUT ELEMENT NAME OF FORM>", "operation" => "AND|OR", "type"=>"BOOLEAN|DATE|TIME|INTEGER|STRING", "datetime_format_from"=>"d/m/Y : Use php date format", "datetime_format_to"=>"d/m/Y", "consider_empty" => "YES|NO : Default - NO"),
            array("condition" => "alias.Column-name = ?", "form-field" => "<INPUT ELEMENT NAME OF FORM>", "operation" => "AND|OR", "type"=>"BOOLEAN|DATE|DATETIME|TIME|INTEGER|STRING", "datetime_format_from"=>"d/m/Y : Use php date format", "datetime_format_to"=>"PHP date format d/m/Y", "consider_empty" => "YES|NO : Default - NO"),
            )
        ,"order" 	=> "<Comma separated order coluns with ASC/DESC key >"
        ,"return_data" => "<HTML / JSON / QUERY>"
        ,"view"	    => "<view location if return_data is HTML>"
        ,"page" 	=> "<page number>"
        ,"pagination" 	=> "YES | NO"
        ,"page_size" => "<page size>"
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
        
        $return_data        = isset($options["return_data"]) ? $options["return_data"] : "JSON";
        $page_size          = isset($_POST['page_size']) ? $_POST['page_size'] : (isset($options["page_size"]) ? $options["page_size"] : 25);
        $page               = isset($_POST['page']) ? $_POST['page'] : (isset($options["page"]) ? $options["page"] : 1);
        $total_records      = isset($_POST['total_records']) ? $_POST['total_records'] : (isset($options["total_records"]) ? $options["page"] : 0);
        $pagination         = isset($options["pagination"]) ? $options["pagination"] : 'YES';
        
        $order              = isset($options["order"]) ? $options["order"] : "";
        $sort               = isset($_POST['sort']) ? $_POST['sort'] : "";
        $sort_type          = isset($_POST['sort-type']) ? $_POST['sort-type'] : "";
        
        $mainData = array(
             "page_size"       => $page_size
            ,"page"            => $page
            ,"total_records"   => $total_records
            ,"next_page"       => 1
            ,"prev_page"       => 1
            ,"last_page"       => 1
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
        if(isset($options['filter']) && is_array($options['filter'])){
            $subCondition = self::ConditionBuilderForFilter($options['filter']);
            $sql .= " WHERE " .  $subCondition;
        } elseif(isset($options['conditions']) && $options['conditions'] !=""){
            $subCondition = self::ConditionBuilder($options['conditions']);
            $sql .= " WHERE " .  $subCondition;
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
                        $stmt = self::$connection->prepare("SELECT COUNT(*) AS count FROM (SELECT 1 " . $count_sql . ") AS query");
                        $stmt->execute();
                        $rec = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        $mainData["total_records"] = $total_records = ($rec["count"]) ? $rec["count"] : 0;
                    }catch(Exception $e){
                        throw new EasyListException("Error in count query : " . $e->getMessage());
                    }
                }
                
                $total_records_pages = intval(ceil($total_records / $page_size));
                $next_page = ($page === $total_records_pages) ? $page : $page + 1;
                $prev_page = ($page == 1) ? 1 : $page - 1;
                $offset    = ($page - 1) * $page_size;
                
                $mainData["next_page"] = $next_page;
                $mainData["prev_page"] = $prev_page;
                $mainData["last_page"] = $total_records_pages;
                
                $sql .= " LIMIT {$offset},{$page_size}";
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
                    $viewData = json_encode($viewData); //, JSON_UNESCAPED_SLASHES
                } else {
                    $viewData = "";
                }
                break;

            case 'JSON' :
                $viewData = $data;
                break;

            case 'QUERY' :
                unset($mainData["page_size"]);
                unset($mainData["page"]);
                unset($mainData["total_records"]);
                unset($mainData["return_data"]);
                unset($mainData["data"]);
                
                $mainData["query"] = $select . $sql;
                $mainData["count_query"] = "SELECT COUNT(*) AS count FROM (SELECT 1 " . $sql . ") AS query";
                $viewData = "";
                break;
        }
    
        $mainData["data"] = $viewData;
        
        $conn = NULL;
        
        return json_encode($mainData);
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
    
        $result = "";
        
        foreach($filter AS $eachfilter){
            $subfilter = "";
            $subValues = "";
            
            $condition = isset($eachfilter['condition']) ? $eachfilter['condition'] : "";
            $postVariable = !isset($eachfilter['form-field']) ? "" : (isset($_POST[$eachfilter['form-field']]) ? $_POST[$eachfilter['form-field']] : "");
            $operation = isset($eachfilter['operation']) ? $eachfilter['operation'] : "";
            $type = isset($eachfilter['type']) ? trim(strtoupper($eachfilter['type'])) : "STRING";
            $dateFormatFrom = isset($eachfilter['datetime_format_from']) ? trim($eachfilter['datetime_format_from']) : "Y-m-d";
            $dateFormatTo = isset($eachfilter['datetime_format_to']) ? trim($eachfilter['datetime_format_to']) : "Y-m-d";
            $emoty_consider = isset($eachfilter['consider_empty']) ? trim(strtoupper($eachfilter['consider_empty'])) : "NO";
            
            if($condition && ($postVariable || $emoty_consider == "NO")){
                $clean_filter = self::CeanQuotes($condition);
                $ary_subfilter = explode("?", $clean_filter);
                
                $subfilter = $ary_subfilter[0] . " ";
                
                if($type != "ARRAY" && is_array($postVariable)){
                    $postVariable = $postVariable[0];
                }
                
                switch($type){
                    case 'BOOLEAN' :
                        if($postVariable != ""){
                            $subfilter .= "{$postVariable}";
                        } else {
                            $subfilter .= "0";
                        }
                        break;
                    case 'DATETIME' :
                    case 'DATE' :
                    case 'TIME' :
                        $dateObj = DateTime::createFromFormat($dateFormatFrom, $postVariable);
                        if($dateObj){
                            $new_date = $dateObj->format($dateFormatTo);
                            $subfilter .= "'{$new_date}'";
                        } else {
                            throw new EasyListException("Date not matching with the 'datetime_format_from'. ");
                            $subfilter .= "''"; 
                        }
                        break;
                    case 'STRING' :
                        if(stripos($ary_subfilter[0], " LIKE ") !== false){
                            $subfilter = trim($subfilter);
                            $subfilter .= $postVariable;
                        } else {
                            $subfilter .= "'" . $postVariable . "'";
                        }
                        break;
                    case 'ARRAY' :
                        if(is_array($postVariable)){
                            $subValues = "'" . implode("','", $postVariable) . "'";
                            $subfilter .= $subValues;
                        } else {
                            $subfilter .= "'" . $postVariable . "'";
                        }
                        break;
                }
                
                $subfilter .= $ary_subfilter[1] . " " . $eachfilter["operation"] . " ";
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
     */
    public static function List($config)
    {
        if(!array_key_exists($config['column'], $config)){
            $config['column'] = array_map(function($obj){ $obj['sort'] = base64_encode(trim($obj['sort'])); return $obj; }, $config['column']);
        }
        
        $config = rawurlencode(str_replace('null', '""', json_encode($config)));
        
        echo '<input type="text" id="easylist-config" value=\''.$config.'\' />
             <div class="text-center" id="div-list-render"></div>';
    }
    
}
