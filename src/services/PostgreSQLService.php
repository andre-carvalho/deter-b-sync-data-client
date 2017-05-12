<?php

namespace Services;

use DAO\PostgreSQL;
use ValueObjects\DeterbTableStore;
use Configuration\ServiceConfiguration;

/**
 *
 * @abstract Provide up level methods to maintain data into PostgreSQL table.
 *          
 *           This class are stateless.
 *          
 * @since May of 2017
 *       
 * @author andre
 *        
 */
class PostgreSQLService {
	
	/**
	 * Verify if table exists and create then if not.
	 * 
	 * @param string $error, allow read the error message.
	 * @return boolean, true on success or false otherwise.
	 */	
	public static function createTable(&$error) {

		$pg = new PostgreSQL();
		if(!$pg->isConnected()) {
			$error = "No database connect.";
			return false;
		}
		
		$config = ServiceConfiguration::defines();
		$query = "SELECT * FROM ".$config["SCHEMA"].".".$config["DATA_TABLE"]." WHERE 1=2";
		$tableExists = $pg->select($query);
		if( !($tableExists!==false && get_class($tableExists) === "PDOStatement") ) {
			// table doesn't exist. Create then.
			$tableStore = new DeterbTableStore();
			$createTableScript = $tableStore->getSQLToCreateTableStore();
			if(!$pg->execQueryScript($createTableScript, 0)) {
				$error = "Database table (".$config["DATA_TABLE"].") do not exists.";
				$pg->closeConnection();
				return false;
			}
		}
		$pg->closeConnection();
		return true;
	}
	
	/**
	 * Insert data into PostgreSQL table.
	 *
	 * @param string $inputData, the JSON data in text format.
	 * @param string $error, return error message
	 * @return boolean, true on success or false otherwise.
	 */
	public static function pushData($inputData, &$error) {
		if (empty ( $inputData )) {
			$error = "Input data is missing.";
			return false;
		}
		
		$pg = new PostgreSQL();
		if(!$pg->isConnected()) {
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
		
		printf("memory: %d\n",  memory_get_usage(true));
		
		if($pg->execQueryScript($query, $affectedRows) === false )
		{
			// TODO: Write this failure on error log to allow re-execute process to that date. 
			$error = "Failure on execute SQL script.";
			$pg->closeConnection();
			return false;
		}
		$pg->closeConnection();
		return true;
	}
	
	/**
	 * Insert data into PostgreSQL table.
	 *
	 * @param string $inputData, the INSERTs SQL script data in text format.
	 * @param string $error, return error message
	 * @return boolean, true on success or false otherwise.
	 */
	public static function pushRawData($inputData, &$error) {
		if (empty ( $inputData )) {
			$error = "Input data is missing.";
			return false;
		}
	
		$pg = new PostgreSQL();
		if(!$pg->isConnected()) {
			$error = "No database connect.";
			return false;
		}
		
		$affectedRows = substr_count($inputData, "INSERT");
	
		if($pg->execQueryScript($inputData, $affectedRows) === false )
		{
			$error = "Failure on execute SQL script.";
			$pg->closeConnection();
			return false;
		}
		$pg->closeConnection();
		return true;
	}
}