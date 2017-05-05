<?php
namespace DAO;

use LibCurl\LibCurl;
use Log\Log;
use Configuration\ServiceConfiguration;
use PDO;


/**
 * @abstract Allow to connect and run SQL scripts over PostgreSQL service. 
 * 
 * @since January of 2017
 * 
 * @author andre
 *
 */
class PostgreSQL {
	
	protected $conn = NULL;
	protected $logger = NULL;
	protected $logDir = NULL;
	protected $logEnable = true;
	
	function __construct($logDir="log/postgresql") {
		
		$this->logDir = $logDir;
		
		if ( !is_dir($this->logDir) ) {
			if(!mkdir($this->logDir, 0777, true)) {
				// Failed to create log folder. Disabling log!
				$this->logEnable=false;
			}
		}
		
		if($this->logEnable) {
			$this->logger = new Log($this->logDir);
		}
		
		$config = ServiceConfiguration::postgresql();
		
		if (empty ( $config )) {
			$this->logger->log_error("Missing default PostgreSQL configuration.");
		}else {
			if($this->hasDriverPDOPostgreSQL()) {
						//pgsql:host=localhost;port=5432;dbname=testdb;user=bruce;password=mypass
				$dsn = "pgsql:host=" . $config["host"] . ";port=" . $config["port"] . ";dbname=" .
						$config["dbname"] . ";user=" . $config["user"] . ";password=" . $config["pass"];
				$this->conn = new PDO($dsn);
			}else {
				$this->writeErrorLog("The PDO driver to PostgreSQL is not present.");
			}
		}
	}
	
	function __destruct() {
		$this->closeConnection();
	}
	
	private function writeWarningLog($msg="") {
		if(!$this->logEnable) {
			return false;
		}
		if(!empty($msg)) {
			$this->logger->log_warn($msg);
		}
	}
	
	private function writeErrorLog($msg="") {
		if(!$this->logEnable) {
			return false;
		}
		if(!empty($msg)) {
			$this->logger->log_error($msg);
		}
		$this->logger->log_error("ERROR_CODE:" . $this->conn->errorCode());
		$this->logger->log_error("ERROR_INFO:" . $this->conn->errorInfo());
	}
	
	private function hasDriverPDOPostgreSQL() {
		
		$dr=PDO::getAvailableDrivers();
		
		if(in_array("pgsql", $dr, true) ){
			return true;
		}else {
			return false;
		}
	}
	
	public function isConnected() {
		return ($this->conn !== NULL );
	}
	
	public function closeConnection() {
		$this->conn = NULL;
	}
	
	/**
	 * Execute one query with select statement and return the result.
	 * @param string $query, The query to execute.
	 * @return returns a PDOStatement object, or false on failure.
	 */
	public function select($query) {
		$exec=false;
		
		$exec=$this->conn->query($query);
		
		if($exec!==false) {
			return $exec;
		}else {
			$this->writeErrorLog("Fail on execute SELECT query.");
			return false;
		}
	}
	
	/**
	 * Execute a set of the query statements. 
	 * 
	 * @param string $query, the query script to execute.
	 * @param integer $affectedRows, affected lines expected.
	 * @return boolean, true on success or false otherwise.
	 */
	public function execQueryScript($query, $affectedRows) {
		$exec=false;
		
		if(!$this->conn->beginTransaction()) {
			$this->writeErrorLog("Fail to start transaction.");
			return false;
		}
		
		$exec=$this->conn->exec($query);
		
		if($exec!==false && $exec==$affectedRows) {
			if(!$this->conn->commit()) {
				$this->writeErrorLog("Fail to COMMIT transaction.");
				return false;
			}
		}else {
			$this->writeErrorLog("Fail on execute query script.");
			if(!$this->conn->rollBack()) {
				$this->writeErrorLog("Fail to ROLLBACK transaction.");
			}
			return false;
		}
		
		return true;
	}

}