<?php
/**
 * @package EasyList
 */
namespace Easylist\EasyList2;

use Exception;
use PDO;
use PDOException;

class ListConnection
{
    public $host;
    public $username;
    public $password;
    public $database;
    public $protocol;
    public $conn;
    
    public function __construct($db = NULL){
        
        if($db == NULL){
            require_once('app/config/EasyListConfig.php');
        }
        
        $this->host     = $db['host'];
        $this->username = $db['username'];
        $this->password = $db['password'];
        $this->database = $db['database'];
        $this->protocol = trim(strtoupper($db['protocol']));
    }
    
    public function setConnection(){
        try {
            switch($this->protocol){
                case 'MYSQL':
                    $this->conn = new PDO("mysql:host=$this->host;dbname=$this->database", $this->username, $this->password);
                    break;
                case 'SQLSRV':
                    $this->conn = new PDO( "sqlsrv:server=$this->host;Database=$this->database", $this->username, $this->password);
                    break;
                default:
                    $this->conn = null;
                    break;
            }
            
            // set the PDO error mode to exception
            if($this->conn){
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                return $this->conn;
            }else{
                throw new Exception("Unable to connect due to wrong credentials");
            }
            
        } catch(PDOException $e) {
            $this->printDbMesasage("Connection failed: " . $e->getMessage());
        }
    }
    
    private function printDbMesasage($msg){
        echo $msg;
    }

    
}

