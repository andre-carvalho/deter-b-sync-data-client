<?php
/**
 * @filesource index.php
 * 
 * @abstract It is used to load one script with all data from remote database table and write this data over local database table.
 * 
 * @author André Carvalho
 * 
 * @version 2017.05.15
 */

require_once __DIR__ . '/vendor/autoload.php';

use Services\PostgreSQLDataService;
use Services\PostgreSQLLogService;
use Services\HTTPSyncService;
use DAO\GeneralLog;

$log = new GeneralLog();// to write more detailed log in file.
$pgDataService = new PostgreSQLDataService();
$pgLogService = new PostgreSQLLogService();

$error = "";// Used to get the error description from PostgreSQLService methods calls.

$RAWFILE = "";// The directory path and filename to write the raw data during download.

$syncService = new HTTPSyncService();
$RAWFILE = $syncService->downloadAllGeometries();

if($RAWFILE===false) {
	$error = "Failure on download data.";
	$log->writeErrorLog($error);
	if(!$pgLogService->writeLog(0, $error, $RAWFILE)) {
		$log->writeErrorLog("Failure on write the error on log table.");
	}
	exit();
}

// Load all data from file to memory
$data = file_get_contents( $RAWFILE );

if($data===false) {
	$error = "Fail to load all data from file to memory.";
	if(!$pgLogService->writeLog(0, $error, $RAWFILE)) {
		$log->writeErrorLog("Failure on write the error on log table.");
	}
	exit();
}

if(!$pgDataService->renewDataTable($data, $error)) {
	if(!$pgLogService->writeLog(0, $error, $RAWFILE)) {
		$log->writeErrorLog("Failure on write the error on log table.");
	}
	exit();
}

$pgLogService->writeLog(1, "Success", $RAWFILE);