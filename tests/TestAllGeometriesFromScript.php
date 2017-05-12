<?php
/**
 * @filesource TestAllGeometriesFromScript.php
 * 
 * @abstract This test is a simulation of the use service to load one script with all data from remote database table and write this data over local database table.
 * 
 * @author André Carvalho
 * 
 * @version 2017.05.12
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Services\PostgreSQLService;
use DAO\HTTPSyncService;

// used to control the number of times we call the service when one error is find. 
$MAX_REPEAT=5;

$service = new HTTPSyncService();
$fileData = $service->downloadAllGeometries();

while ($fileData===false && $MAX_REPEAT>0) {
	$fileData = $service->downloadAllGeometries();
	echo "Repeat time:".$MAX_REPEAT."\n";
	$MAX_REPEAT--;
}

if($fileData===false) {
	echo "Failure on download data.";
	exit();
}

$data = file_get_contents ( $fileData );

$error = "";
if(!PostgreSQLService::createTable($error)) {
	echo $error;
}else {
	if(!PostgreSQLService::pushRawData($data, $error)) {
		echo $error;
	}
}

echo "\r\nfinish!!";