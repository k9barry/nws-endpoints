<?php

namespace Webhook\Functions;

class recordReceived
{
    /**
     * recordReceived
     *
     * @param  mixed $db_conn
     * @param  mixed $db_incident
     * @param  mixed $strInFile
     * @return void
     */
    public function recordReceived($db_conn, $db_incident, $strInFile) // called from monitor.php

    {
        $xml = simplexml_load_file($strInFile) or die("Error: Cannot create object");
        $logger->info("File " . $strInFile . " read into simpleXML");
        if ($xml->ClosedFlag == "true") { //record is closed
            $logger->info("ClosedFlag is true so remove record " . $xml->CallId . " from db");
            deleteRecord($db_conn, $db_incident, $xml->CallId);
            return;
        } elseif (!callIdExist($db_conn, $db_incident, $xml->CallId)) { // record does not exist in db
            $logger->info("New record entered into DB set send = 1");
            insertRecord($db_conn, $db_incident, $xml, $send = 1); // This is where a new record gets entered into db
        } else {
            $logger->info("Record exists in DB - check for changes to requsite fields");
            insertRecord($db_conn, $db_incident, $xml, $send = 0); // Record exists in DB-check for changes to in fields
        }
    }
}
