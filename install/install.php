<?php
/**
 * Provide a simple way to build the environment to run this client.
 * - Create necessary directories such as: tmp, config, log and rawData
 * - Create the config file as template
 */

$directories = array("config", "log", "rawData", "tmp");

foreach ($directories as $dir) {
	$d=__DIR__ . "/../" . $dir;
	if(!is_dir($d)) {
		if(mkdir($d)===false) {
			echo "The installation was failed in create the directory ".$d;
			echo "Maybe is missing permissions.";
			exit();
		}
	}
}

$configTemplate = "<?php\n".
"// ServiceConfiguration.php\n".
"namespace Configuration;\n".
"\n".
"class ServiceConfiguration {\n".
"	\n".
"	public static function syncservice() {\n".
"		\$config = array (\n".
"				'host' => 'http://<your_host_name>/',\n".
"				'user' => 'username',\n".
"				'pass' => 'password',\n".
"				'max_times' => 5,// The maximum number of times that client attempt to connect with service.\n".
"				'timeout' => 60// Time waiting the service response (wait 60 seconds before send timeout signal).\n".
"		);\n".
"		return \$config;\n".
"	}\n".
"	\n".
"	public static function postgresql() {\n".
"		\$config = array (\n".
"				'host' => 'localhost',\n".
"				'user' => 'postgres',\n".
"				'pass' => 'postgres',\n".
"				'dbname' => 'DB_NAME',\n".
"				'port' => 5432\n".
"		);\n".
"		return \$config;\n".
"	}\n".
"	\n".
"	public static function defines() {\n".
"		\$config = array (\n".
"				'SRID' => 4674,// (SIRGAS 2000)\n".
"				'SCHEMA' => 'public',\n".
"				'DATA_TABLE' => 'your_table_name',\n".
"				'LOG_TABLE' => 'your_log_table_name'\n".
"		);\n".
"		return \$config;\n".
"	}\n".
"	\n".
"	public static function ssmtp() {\n".
"		\$config = array (\n".
"				'TO' => 'carvalho@dpi.inpe.br',\n".
"				'FROM' => 'andrefuncate@gmail.com'\n".
"		);\n".
"		return \$config;\n".
"	}\n".
"}\n".
"\n";

$configFileName = __DIR__ . "/../config/ServiceConfiguration.php";
$handle = fopen($configFileName, 'w');
if($handle===false) {
	echo "The file creator was fail when attempt create the configuration file.";
	exit();
}
fwrite($handle, $configTemplate);
fclose($handle);