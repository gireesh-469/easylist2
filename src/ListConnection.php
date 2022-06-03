<?php
/**
 * @package EasyList2
 */
namespace EasyList2;

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
    public $port; //newly added
    public $dsn_service;//newly added
    public $dsn_protocol;// newly added
    public $ids_server;// newly added
    
    public function __construct($db = NULL){
        if($db == NULL){
            require_once('app/config/EasyListConfig.php');
        }
        
        $this->host        = $db['host'];
        $this->username    = $db['username'];
        $this->password    = $db['password'];
        $this->database    = $db['database'];
        $this->protocol    = trim(strtoupper($db['protocol']));
        $this->port        = (!empty($db['port'])) ? $db['port'] : null;//newly added
        $this->dsn_service = (!empty($db['dsn_service'])) ? $db['dsn_service'] : null;//newly added
        $this->dsn_protocol= (!empty($db['dsn_protocol'])) ? $db['dsn_protocol'] : null;//newly added
        $this->ids_server  = (!empty($db['ids_server'])) ? $db['ids_server'] : null;//newly added
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
                case 'ORACLE':
                    $this->conn = new PDO( "oci:dbname=$this->database", $this->username, $this->password);//newly added
                    break;
                case 'POSTGRESQL':
                    $this->conn = new PDO("pgsql:host=$this->host;dbname=$this->database", $this->username, $this->password);// newly added
                    break;
                case 'SYBASE':
                    $this->conn = new PDO ("dblib:host=$this->host:$this->port;dbname=$this->database","$this->username","$this->password"); // newly added
                    //port = 10060;
                    break;
                case 'INFORMIX':
                    $this->conn = new PDO("informix:host=$this->host; service=$this->dsn_service;database=$this->database; server=$this->ids_server; protocol=$this->dsn_protocol;EnableScrollableCursors=1", "$this->username ", "$this->password"); // newly added
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
