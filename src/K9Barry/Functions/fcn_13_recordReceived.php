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
    $xml = simplexml_load_file($strInFile) or die("Error: Cannot create object"); # read the xml file
    $logger->info("File " . $strInFile . " read into simpleXML");

// $AgencyContexts_AgencyContext_AgencyType = $xml->AgencyContexts->AgencyContext[0]->AgencyType;
$agencies = $sep = '';
$nrOfRows = $xml->AgencyContexts->AgencyContext->count();
$n = 0;
for ($n = 0; $n < $nrOfRows; $n++) {
    $value = $xml->AgencyContexts->AgencyContext[$n]->AgencyType;
    $agencies .= $sep . $value;
    $sep = '|';
}
var_dump($agencies);
echo "\r\n";

// $Incidents_Incident_Jurisdiction = $xml->Incidents->Incident->Jurisdiction;
$jurisdictions = $sep = '';
$nrOfRows = $xml->Incidents->Incident->count();
$n = 0;
for ($n = 0; $n < $nrOfRows; $n++) {
    $value = $xml->Incidents->Incident[$n]->Jurisdiction;
    $jurisdictions .= $sep . $value;
    $sep = '|';
}
var_dump($jurisdictions);
echo "\r\n";

// $AssignedUnits_Unit_UnitNumber = $xml->AssignedUnits->Unit->UnitNumber;
$units = $sep = '';
$nrOfRows = $xml->AssignedUnits->Unit->count();
$n = 0;
for ($n = 0; $n < $nrOfRows; $n++) {
    $value = $xml->AssignedUnits->Unit[$n]->UnitNumber;
    $units .= $sep . $value;
    $sep = '|';
}
var_dump($units);
echo "\r\n";

#Gather all topics to send to
$topics = "" . $agencies . "|" . $jurisdictions . "|" . $units . "";
$arr_Topics_Xml = array_filter(explode('|', $topics));



    if ($xml->ClosedFlag == "true") { //record is closed
        $logger->info("ClosedFlag is true so remove record " . $xml->CallId . " from db");
        fcn_14_deleteRecord($db_conn, $db_incident, $xml->CallId, $logger);
        return;
    } elseif (!fcn_15_callIdExist($db_conn, $db_incident, $xml->CallId, $logger)) { // record does not exist in db
        $logger->info("New record entered lets get the topics.");


        echo "Record does not exist so we will send all topics: $topics \r\n";
        #echo "".$send."\r\n";

        fcn_16_insertRecord($db_conn, $db_incident, $xml, $send = 1, $logger); // This is where a new record gets entered into db

    } else {
        $logger->info("Record exists in DB - gathering topic changes and checkig  for changes to requsite fields");

#Load the info from the db
$CallId = $xml->CallId;
$sql = "SELECT * FROM $db_incident WHERE db_CallId = '$CallId'";
$row = $db_conn->prepare($sql);
$row->execute();
$ntfyMessage = $row->fetchAll(PDO::FETCH_ASSOC);
$out = $sep = '';
foreach ($ntfyMessage[0] as $key => $value) {
    $out .= $sep . $key . ":" . $value . "\n";
    $sep = '';
}
extract($ntfyMessage[0]);

        #Get the topics from the DB file
        $topics_arrDb_Agency = explode("|", $db_AgencyType);
        $topics_arrDb_Jurisdiction = explode("|", $db_Incident_Jurisdiction);
        $topics_arrDb_Unit = explode("|", $db_UnitNumber);
        $arr_Topics_Db = array_merge($topics_arrDb_Agency, $topics_arrDb_Jurisdiction, $topics_arrDb_Unit);
                
        #Get the topic differences between the xml file and the DB
        $topics = array_diff($topics_arrXml, $topics_arrDb);

        #If the count of the array is >0 then resend the message to the new topic
        if (count($topics) > 0) {
            $send = 1;
            $logger->info("########### Ntfy messages will be sent to " . $topics . " #############");
        } else {
            $send = 0;
            $logger->info("No new topics - nothing to send");
        }

        #Check to see if the call type changes if so resend to all topics


        #Check to see if the location changes if so resend to all topics




        echo "Record exists so we will send only the new topics \r\n";

        fcn_16_insertRecord($db_conn, $db_incident, $xml, $send = 0, $logger); // Record exists in DB-check for changes to in fields
    }
}
