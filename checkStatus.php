<?php
/**
 * @filesource checkStatus.php
 * 
 * @abstract This script read the last state and max date of data of the ingestion process.
 * After that, writes this informations in the text file and send by e-mail.
 * 
 * @author André Carvalho
 * 
 * @version 2017.07.06
 */
require_once __DIR__ . '/vendor/autoload.php';

error_reporting(E_ALL & ~E_NOTICE);

use Services\PostgreSQLLogService;
use Services\PostgreSQLDataService;
use DAO\GeneralLog;

$log = new GeneralLog();// to write more detailed log in file.
$pgLogService = new PostgreSQLLogService();
$pgDataService = new PostgreSQLDataService();

if(get_class($log)!=='DAO\\GeneralLog') {
	// disable logs in file
	$log=false;
}

if(get_class($pgLogService)!=='Services\\PostgreSQLLogService') {
	// Abort the process because we don't access the database server to write log.
	if($log) $log->writeErrorLog("Abort the process because we don't access the database server to write log.");
	exit();
}

if(get_class($pgDataService)!=='Services\\PostgreSQLDataService') {
	// Abort the process because we don't access the database server to write data.
	if($log) $log->writeErrorLog("Abort the process because we don't access the database server to write data.");
	exit();
}

$data = ""; // Data to send via e-mail
$error = "";// Used to get the error description from PostgreSQLService methods calls.

$maxDate = $pgDataService->readMaxDate($error);
if(!$maxDate) {
	if($log) $log->writeErrorLog($error . "\nFailure on read the max date from data table.");
}else {
	$date = new DateTime($maxDate[0]['max']);
	
	$data = "Data do dado mais recente no banco: ".$date->format('d-m-Y')."\n";
}

$lastState = $pgLogService->readLastStateFromLogTable($error);
if(!$lastState) {
	if($log) $log->writeErrorLog($error . "\nFailure on read the last state from log table.");
}else {
	
	$date = new DateTime($lastState[0]['date']);
	
	$data .= "\n";
	$data .= "Data da última sincronização: ".$date->format('d-m-Y')."\n";
	$data .= "Esta sincronia foi executada com: ".( ($lastState[0]['state']===0)?("falha"):("sucesso") )."\n";
	if($lastState[0]['state']===0) {
		$data .= "Detalhes da falha:\n".
		"--------------------------------------------\n".
		$lastState[0]['detail']."\n".
		"--------------------------------------------\n";
	}
}

if(!empty($data)) {
	$data = "To:carvalho@dpi.inpe.br\n".
			"From:andrefuncate@gmail.com\n".
			"Subject: [DETER-B] - Daily check synchronize data.\n".
			"Content-Type: text/plain; charset=\"utf-8\";\n".
			$data.
			"Este email é de uso exclusivo da equipe de TI do INPE.\n".
			"Caso tenha recebido este email inapropriadamente, descarte-o.\n".
			"Att. Equipe do projeto DETER-B.\n";
}

$mailContentFile = __DIR__ . "/tmp/mail-content.txt";

if(!empty($data) && file_put_contents($mailContentFile, $data)!==false) {
	//echo ("sendmail carvalho@dpi.inpe.br < " . realpath($mailContentFile));
	exec("sendmail carvalho@dpi.inpe.br < " . realpath($mailContentFile));
}