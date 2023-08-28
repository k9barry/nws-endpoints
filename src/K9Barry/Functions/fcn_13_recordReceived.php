<?php

/**
 * fcn_13_recordReceived
 *
 * @param  mixed $db_conn
 * @param  mixed $db_incident
 * @param  mixed $strInFile
 * @param  mixed $logger
 * @return void
 */
function fcn_13_recordReceived($db_conn, $db_incident, $strInFile, $logger)

{
    $xml = simplexml_load_file($strInFile) or die("Error: Cannot create object");
    $logger->info("File " . $strInFile . " read into simpleXML");
    if ($xml->ClosedFlag == "true") { //record is closed
        $logger->info("ClosedFlag is true so remove record " . $xml->CallId . " from db");
        fcn_14_deleteRecord($db_conn, $db_incident, $xml->CallId, $logger);
        return;
    } elseif (!fcn_15_callIdExist($db_conn, $db_incident, $xml->CallId, $logger)) { // record does not exist in db
        $logger->info("New record entered into DB set send = 1");
        fcn_16_insertRecord($db_conn, $db_incident, $xml, $send = 1, $logger); // This is where a new record gets entered into db
    } else {
        $logger->info("Record exists in DB - check for changes to requsite fields");
        fcn_16_insertRecord($db_conn, $db_incident, $xml, $send = 0, $logger); // Record exists in DB-check for changes to in fields
    }
}
