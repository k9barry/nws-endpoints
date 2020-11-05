<?php

/**
 * Composer autoload
 *
 */

require_once "./vendor/autoload.php";

/**
 * Load Monolog and Initialize
 *
 */

use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Monolog\Processor\IntrospectionProcessor;

$logger = new Logger('webhook_logger'); // Create the logger
$logger->pushHandler(new RotatingFileHandler("./data/Logs/webhook.log", Logger::DEBUG)); // Add sRotatingFileHandler
$logger->pushProcessor(new IntrospectionProcessor());
$logger->info('Webhook logger is now ready'); // You can now use your logger

/**
 * Require config file
 *
 */

$configfile = "./config.php";
if (file_exists($configfile)) {
    require_once $configfile;
} else {
    die("Unable to locate config.php file");
}

/**
 * Require functions
 *
 */
foreach (glob('./src/K9Barry/Webhook/*.php') as $filename) {
    include_once $filename;
    $logger->info("include_once $filename \r\n");
}

/**
 * startScript
 *
 */

ini_set('memory_limit', '-1');
ini_set("max_execution_time", 0);
set_time_limit(0);

if (!is_dir($strOutFolder)) {
    mkdir($strOutFolder);
}
if (!is_dir($strBackupFolder)) {
    mkdir($strBackupFolder);
}
while (true) {
    MonitorFolderAndCallEXE($strInFolder, $arrayInputFileExtensions, $strOutFolder, $strBackupFolder);
    $logger->info('[Start/End] =====================================================');
    sleep($sleep); //Waiting for XXX seconds
}
