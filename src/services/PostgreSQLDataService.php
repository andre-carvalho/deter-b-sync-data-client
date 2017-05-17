<?php

namespace Services;

use DAO\PostgreSQL;
use ValueObjects\DeterbTableStore;
use Configuration\ServiceConfiguration;

/**
 *
 * @abstract Provide up level methods to maintain data into PostgreSQL table.
 *          
 * @since May of 2017
 *       
 * @author andre
 *        
 */
class PostgreSQLDataService extends PostgreSQLService {
	
	/**
	 * Verify if data table exists and create then if not.
	 * 
	 * @param string $error, allow read the error message.
	 * @return boolean, true on success or false otherwise.
	 */	
	public function createDataTable(&$error) {
		$config = ServiceConfiguration::defines();
		
		if( empty ( $config ) ) {
			$error = "Missing the metadata tables configuration.";
			return false;
		}
		
		$tableName = $config["SCHEMA"].".".$config["DATA_TABLE"];
		return $this->createTable($tableName, DeterbTableStore::getSQLToCreateTableStore(), $error);
	}
	
	/**
	 * Drop the Data table.
	 * @param string $error, allow read the error message.
	 * @return boolean, true on success or false otherwise.
	 */
	public function dropDataTable(&$error) {

		$config = ServiceConfiguration::defines();

		if( empty ( $config ) ) {
			$error = "Missing the metadata tables configuration.";
			return false;
		}

		$tableName = $config["SCHEMA"].".".$config["DATA_TABLE"];
		$query = "DROP TABLE IF EXISTS ".$tableName.";";
		return $this->dropTable($tableName, $query, $error);
	}
	
	/**
	 * Insert data into PostgreSQL table.
	 *
	 * @param string $inputData, the INSERTs SQL script data in text format.
	 * @param string $error, return error message
	 * @return boolean, true on success or false otherwise.
	 */
	public function pushRawData($inputData, &$error) {
		if (empty ( $inputData )) {
			$error = "Input data is missing.";
			return false;
		}
		
		if(!$this->pg) {
			$error = "No database connect.";
			return false;
		}
	
		if($this->pg->execQueryScript($inputData) === false )
		{
			$error = "Failure on execute SQL script.";
			return false;
		}
		return true;
	}
	
	/**
	 * Execute the complete process to renew data table in local PostgreSQL database into a unique transaction.
	 * The steps:
	 * 	- Drop data table;
	 * 	- Create data table;
	 *  - Insert new data into table;
	 *
	 * @param string $data, the new SQL script data in text format.
	 * @param string $error, return error message
	 * @return boolean, true on success or false otherwise.
	 */
	public function renewDataTable($data, &$error) {
		
		if(empty($data)) {
			$error = "Missing data";
			$this->writeErrorLog($error);
			return false;
		}
		
		if(!$this->start()) {// begin
			$error = "Begin command has failed.";
			$this->writeErrorLog($error);
			return false;
		}
		
		if(!$this->dropDataTable($error)) {
			$error .= "\nFailure on DROP the old database table.";
			$this->writeErrorLog($error);
			if($this->stop(false)){// rollback
				$error .= "\nRollback command has failed.";
			}
			return false;
		}

		if(!$this->createDataTable($error)) {
			
			$error .= "\nFailure on create data table.";
			$this->writeErrorLog($error);
			if($this->stop(false)){// rollback
				$error .= "\nRollback command has failed.";
			}
			return false;
		}else {
			
			if(!$this->pushRawData($data, $error)) {
				$error .= "\nFailure on push data to data table.";
				$this->writeErrorLog($error);
				if($this->stop(false)){// rollback
					$error .= "\nRollback command has failed.";
				}
				return false;
			}
		}
		
		if($this->stop(true)===false){// commit
			$error .= "\nCommit command has failed.";
			return false;
		}
		return true;
	}
	
	/**
	 * WARNING: THIS METHOD ARE USED ON ANOTHER CENARIUS WHEN SERVICE RESPONSES IN JSON FORMAT.
	 * Insert data into PostgreSQL table.
	 *
	 * @param string $inputData, the JSON data in text format.
	 * @param string $error, return error message
	 * @return boolean, true on success or false otherwise.
	 */
	public function pushData($inputData, &$error) {
		if (empty ( $inputData )) {
			$error = "Input data is missing.";
			return false;
		}
	
		if(!$this->pg) {
			$error = "No database connect.";
			return false;
		}
	
		$tableStore = new DeterbTableStore($inputData);
	
		$insertNumRows=0;
		$inserts = $tableStore->toSQLInsert($insertNumRows);
		$updateNumRows=0;
		$updates = $tableStore->toSQLUpdate($updateNumRows);
		$deleteNumRows=0;
		$deletes = $tableStore->toSQLDelete($deleteNumRows);
	
		$query = $inserts . $updates . $deletes;
		$affectedRows = $insertNumRows + $updateNumRows + $deleteNumRows;// TODO: Verify if inserts generate the rows affected after execute.
	
		//printf("memory: %d\n",  memory_get_usage(true));
		if($this->pg->execQueryScript($query) === false ) //, $affectedRows) === false )
		{
			$error = "Failure on execute SQL script.";
			return false;
		}
		return true;
	}
}