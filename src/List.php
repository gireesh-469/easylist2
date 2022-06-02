<?php
/**
 * @package EasyList
 */
namespace Easylist\EasyList2;

use Exception;
use PDO;
use PDOException;

class DynaList
{
    //public static $host;
    //public static $database;
    //public static $username;
    //public static $password;
    public static $connection;
    
    /**
     * @param array $info
     * Description : Store sql credential 
     */
    /*public static function Initialise($info)
    {
        static::$host     = $info['hostname'];
        static::$database = $info['database'];
        static::$username = $info['username'];
        static::$password = $info['password'];
    }*/
    
    /**
     * Creates Connection
     */
    public static function Connection()
    {
        /*try {
            self::$connection = new PDO("mysql:host=".static::$host.";dbname=". static::$database, static::$username, static::$password);
            self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            throw new Exception("Connection failed : " . $e->getMessage());
        }*/
        
        $conn = new ListConnection();
        self::$connection = $conn->setConnection();
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
        ,"order" 	=> "<Comma separated order coluns with ASC/DESC key >"
        ,"return-data" => "<HTML / JSON / PLAIN / QUERY>"
        ,"view"	    => "<view location if return-data is HTML>"
        ,"page" 	=> "<page number>"
        ,"pagination" 	=> "YES | NO"
        ,"page-size" => "<page size>"
       )
     */
    public static function Page($options)
    {
        $sql                = "";
        $select             = "";
        $query              = "";
        $viewData           = "";
        $subCondition       = "";
        
        $return_data        = isset($options["return-data"]) ? $options["return-data"] : "JSON";
        $page_size          = isset($_POST['page-size']) ? $_POST['page-size'] : (isset($options["page-size"]) ? $options["page-size"] : 25);
        $page               = isset($_POST['page']) ? $_POST['page'] : (isset($options["page"]) ? $options["page"] : 1);
        $total_records      = isset($_POST['total-records']) ? $_POST['total-records'] : (isset($options["total-records"]) ? $options["page"] : 0);
        $pagination         = isset($options["pagination"]) ? $options["pagination"] : 'YES';
        
        $mainData = array(
             "page-size"       => $page_size
            ,"page"            => $page
            ,"total-records"   => $total_records
            ,"return-data"     => $return_data
            ,"data"            => array()
        );
        
        $data = array();
        
        try{
            if(!isset($options['select']) || trim($options['select']) == "" || !isset($options['from']) || trim($options['from']) == "" ){
                throw new Exception("Select OR From clause is missing.");
            } else {
                $select = "SELECT " . $options['select'];
                $sql .= " FROM " . $options['from'];
            }
            
            if(isset($options['joins']) && $options['joins'] !=""){
                $sql .= $options['joins'];
            }
            
            if(isset($options['conditions']) && $options['conditions'] !=""){
                $subCondition = self::conditionBuilder($options['conditions']);
                $sql .= " WHERE " .  $subCondition;
            }
            
            if(isset($options['group']) && $options['group'] !=""){
                $sql .= " GROUP BY " .  $options['group'];
            }
            
            if(isset($options['having']) && $options['having'] !=""){
                $subCondition = self::conditionBuilder($options['having']);
                $sql .=  " HAVING " . $subCondition;
            }
            
            if(isset($options['order']) && $options['order'] !=""){
                $sql .=  " ORDER BY " . $options['order'];
            }
                    
            if($return_data != "QUERY"){
                self::Connection();
                //$conn = mysqli_connect(self::$host, self::$username, self::$password) OR trigger_error(mysql_error(),E_USER_ERROR);
                //mysql_select_db(self::$database, $conn);
                
                //Start : Pagination section 
                if($pagination == "YES"){
                    if($total_records == 0){
                        
                        $stmt = self::$connection->prepare("SELECT COUNT(*) AS count FROM (SELECT 1 " . $sql . ") AS query");
                        $stmt->execute();
                        $rec = $stmt->fetch(PDO::FETCH_ASSOC);
                        //$query = mysqli_query($conn, "SELECT COUNT(*) AS count FROM (SELECT 1 " . $sql . ") AS query") OR trigger_error(mysql_error(),E_USER_ERROR);
                        //$rec = mysql_fetch_assoc($query);
                        $mainData["total-records"] = $total_records = ($rec["count"]) ? $rec["count"] : 0;
                    }
                    
                    $total_records_pages = intval(ceil($total_records / $page_size));
                    $next_page = ($page === $total_records_pages) ? $page : $page + 1;
                    $prev_page = ($page == 1) ? 1 : $page - 1;
                    $offset    = ($page - 1) * $page_size;
                    
                    $mainData["next-page"] = $next_page;
                    $mainData["prev-page"] = $prev_page;
                    
                    $sql .= " LIMIT {$offset},{$page_size}";
                }
                //End : Pagination section
                
                $stmt = self::$connection->prepare($select . $sql);
                $stmt->execute();
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                //$query = mysqli_query($conn, $select . $sql) OR trigger_error(mysql_error(),E_USER_ERROR);
                //if (mysql_num_rows($query) > 0) {
                //    while ($row = mysql_fetch_assoc($query)) {
                //        $data[] = $row;
                //    }
                //}
            }
            
            //Handling return data
            switch($return_data){
                case 'HTML' :
                    //:TODO
                    $viewData = "";
                    break;

                case 'JSON' :
                    $viewData = $data;
                    break;

                case 'PLAIN' :
                    //:TODO
                    $viewData = "";
                    break;

                case 'QUERY' :
                    unset($mainData["page-size"]);
                    unset($mainData["page"]);
                    unset($mainData["total-records"]);
                    unset($mainData["return-data"]);
                    unset($mainData["data"]);
                    
                    $mainData["query"] = $select . $sql;
                    $mainData["count-query"] = "SELECT COUNT(*) AS count FROM (SELECT 1 " . $sql . ") AS query";
                    $viewData = "";
                    break;
            }
        
            $mainData["data"] = $viewData;
            
            mysqli_close($conn);
            
        } catch(Exception $e){
            //throw new Exception($e-getMessage());
        }
        
        return json_encode($mainData);
    }
    
    /**
     * @param array $condition
     * @return string
     * Description : Prepare condtions by including values 
     */
    public static function conditionBuilder($condition){
        $result = "";
        
        foreach($condition AS $eachCondition){
            $subCondition = "";
            $subValues = "";
            
            $clean_condition = self::ceanQuotes($eachCondition['condition']);
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
    public static function ceanQuotes($condition){
        $result = $condition;
        
        $pattern = "/'\s*\?\s*'/i";
        $result = preg_replace($pattern, ' ? ', $result);
        $pattern = '/"\s*\?\s*"/i';
        $result = preg_replace($pattern, ' ? ', $result);
        
        return $result;
    }
    
    
    
    
    /**
     * @param array $options
     */
    public static function List($options)
    {
    }
    
    
    
    
}