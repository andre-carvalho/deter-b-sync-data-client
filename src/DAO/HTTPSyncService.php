<?php
namespace DAO;

use LibCurl\LibCurl;
use Log\Log;
use Configuration\ServiceConfiguration;
use ValueObjects\DeterbTupleStore;
use DateTime;
use \DateTimeZone;

/**
 * @abstract Allow to communicate with HTTP Sync Service via API to read data.
 * 
 * @uses By default the log is writen on local directory /log/sync_service.
 * 
 * @since May of 2017
 * 
 * @author andre
 *
 */
class HTTPSyncService {
	
	protected $hashKey = null;
	protected $curl = null;
	protected $logger = null;
	protected $logDir = null;
	protected $logEnable = true;
	
	function __construct($logDir="log/sync_service") {
		
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
		
		$this->curl = new LibCurl();
		$this->doAuthentication();
	}
	
	function __destruct() {
		$this->curl->close();
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
		if ($this->curl->error) {
			$this->logger->log_error("ErrorCode:".$this->curl->errorCode);
			$this->logger->log_error("ErrorMsg:".$this->curl->errorMessage);
		}
	}
	
	/**
	 * Do authentication and store hash key to this session in memory.
	 */
	private function doAuthentication() {
		$config = ServiceConfiguration::syncservice();
		$bodyData = '{"usuario":"'.$config["user"].'", "senha":"'.$config["pass"].'"}';
		$host = $config["host"].'login';
		$this->hashKey = $this->curl->post($host, $bodyData);
	}
	
	/**
	 * Load all geometries from service for insert the initial population into local table.
	 * The tipical url is: http://<URL_BASE>/allgeometries?hashKey=<auth_key>
	 * @return DeterbTableStore or false: Return the DeterbTableStore instance or false otherwise.
	 */
	public function getAllGeometries() {
		$config = ServiceConfiguration::syncservice();
		
		$URL = $config["host"].'allgeometries?hashKey='.$this->hashKey;
		//$this->curl->resetCurl();
		$this->curl->get($URL);
		
		if ($this->curl->error) {
			$this->writeErrorLog();
			return false;
		}
		
		$tableStore=false;
		
		if($this->curl->responseHeaders['Status-Line']=="HTTP/1.1 200 OK" && $this->curl->responseHeaders['Content-Type']=="application/json") {
			$jsonResponse = $this->curl->response;
			if(isset($jsonResponse->inserts) || isset($jsonResponse->updates) || isset($jsonResponse->deletes)) {
				$tableStore = new DeterbTableStore($jsonResponse);
			}else {
				$this->writeErrorLog("No data on remote table store.");
				return false;
			}
		}else {
			$this->writeErrorLog("Failure of response test on getAllGeometries.");
			return false;
		}
		
		return $tableStore;
	}
	
	/**
	 * Load all geometries in SQL script format as from service for insert into local table.
	 * The tipical url is: http://<URL_BASE>/allgeometries?hashKey=<auth_key>
	 * @return string or false: Return the name of read file or false otherwise.
	 */
	public function downloadAllGeometries() {
		$config = ServiceConfiguration::syncservice();
	
		$URL = $config["host"].'allgeometries?hashKey='.$this->hashKey;

		
		// USE TEST URL
		$URL = 'http://200.18.85.235/detertool/allgeometries/f80220b6c3a6da4a2e3bc527d8a856ca';
		//$URL = 'http://localhost/html/sleep.php';
		//$this->curl->resetCurl(); // TODO: see if this is necessary...
		
		// This is the file where we save the information
		$dt = new DateTime();
		$dt->setTimeZone(new DateTimeZone('America/Sao_Paulo'));
		$baseFileName = $dt->format('d-m-Y') . "_all_data";
		$tmpFile = __DIR__ . '/../../tmp/'.$baseFileName.'.tmp';
		
		$fp = fopen( $tmpFile, 'w+');
		if($fp===false) {
			$this->writeErrorLog("Fail on open the temporary file.");
			return false;
		}
		
		// sets curl option to save response directly to a file
		@$this->curl->setOption(CURLOPT_HEADER, 0);
		$this->curl->setOption(CURLOPT_TIMEOUT, 20);// wait 20 seconds before send timeout signal.
		$this->curl->setOption(CURLOPT_FOLLOWLOCATION, true);
		$this->curl->setOption(CURLOPT_FILE, $fp);// write curl response to file
		
		$this->curl->get($URL);
			
		if ($this->curl->error) {
			$this->writeErrorLog();
			fclose($fp);
			unlink($tmpFile);
			return false;
		}
	
		if($this->curl->response===true && $this->curl->httpStatusCode===200) { // && $this->curl->responseHeaders['Content-Type']=="text/plain") {
			// move temporary file to rawData directory
			$finalFile = __DIR__ . '/../../rawData/' . $baseFileName . '.sql';
			
			if(rename($tmpFile, $finalFile)===false) {
				$this->writeErrorLog("Failure on move temporary file to work directory.");
				fclose($fp);
				unlink($tmpFile);
				return false;
			}
			
			fclose($fp);
			return $finalFile;
		}else {
			$this->writeErrorLog("Failure of response from downloadAllGeometries call.");
			fclose($fp);
			unlink($tmpFile);
			return false;
		}
	}
	
}