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
		$result = $pg->select($query);
		if( $result!==false && get_class($result) == "PDOStatement" ) {
			// table exists
		}else {
			// table doesn't exist. Create then.
		}
		// TODO: Lack more implementation...
	}
	
	/**
	 * Insert data into PostgreSQL table.
	 *
	 * @param string $inputData, the JSON data.
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
		
		if($pg->execQueryScript($query, $affectedRows) === false )
		{
			// TODO: Write this failure on error log to allow re-execute process to that date. 
			$error = "Failure on execute SQL script.";
			return false;
		}
		
		return true;
	}
}