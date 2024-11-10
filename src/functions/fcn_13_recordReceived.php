<?php

/**
 * fcn_13_recordReceived
 *
 * @param  mixed $db_conn
 * @param  string $db_incident
 * @param  string $strInFile
 * @param  mixed $logger
 * @return void
 */
function fcn_13_recordReceived($db_conn, $db_incident, $strInFile, $logger)
{
    global $TimeAdjust;
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
    $agencies = implode("|", array_unique(explode("|", $agencies))); //remove any duplicates

    // $Incidents_Incident_Jurisdiction = $xml->Incidents->Incident->Jurisdiction;
    $jurisdictions = $sep = '';
    $nrOfRows = $xml->Incidents->Incident->count();
    $n = 0;
    for ($n = 0; $n < $nrOfRows; $n++) {
        $value = $xml->Incidents->Incident[$n]->Jurisdiction;
        $jurisdictions .= $sep . $value;
        $sep = '|';
    }
    $jurisdictions = implode("|", array_unique(explode("|", $jurisdictions))); //remove any duplicates

    // $AssignedUnits_Unit_UnitNumber = $xml->AssignedUnits->Unit->UnitNumber;
    $units = $sep = '';
    $nrOfRows = $xml->AssignedUnits->Unit->count();
    $n = 0;
    for ($n = 0; $n < $nrOfRows; $n++) {
        $value = $xml->AssignedUnits->Unit[$n]->UnitNumber;
        $units .= $sep . $value;
        $sep = '|';
    }

    #Gather all topics to send to
    $topics = ""; //Set to null
    $topics = "" . $agencies . "|" . $jurisdictions . "|" . $units . "";
    $arr_Topics_Xml = array_unique(explode('|', $topics));
    #echo "XML topics are: ".var_dump($arr_Topics_Xml)." \r\n";

    #Delta time check
    $delta = fcn_20_DeltaTime($xml->CreateDateTime);
    if ($xml->ClosedFlag == "true") { //record is closed
        $logger->info("ClosedFlag is true so remove record " . $xml->CallId . " from db");
        fcn_14_deleteRecord($db_conn, $db_incident, $xml->CallId, $logger);
        return;
    } else if (!fcn_15_callIdExist($db_conn, $db_incident, $xml->CallId, $logger)) { // record does not exist in db
        $logger->info("New record to enter into the DB and send to all topics.");
        #echo "Record does not exist so we will insert into DB: ".$topics." \r\n";
        fcn_16_insertRecord($db_conn, $db_incident, $xml, $logger, $agencies, $jurisdictions, $units); // This is where a new record gets entered into db
        fcn_21_sendNtfy($db_conn, $db_incident, $xml, $delta, $logger, $topics, $resendAll = 0); // Send to ntfy
    } else {
        $logger->info("Record exists in DB - gathering topic changes and checking for changes to requsite fields");
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
        $topics = ""; //Set to null
        $topics_arrDb_Agency = array_unique(explode("|", $db_AgencyType));
        $topics_arrDb_Jurisdiction = array_unique(explode("|", $db_Incident_Jurisdiction));
        $topics_arrDb_Unit = array_unique(explode("|", $db_UnitNumber));
        $arr_Topics_Db = array_merge($topics_arrDb_Agency, $topics_arrDb_Jurisdiction, $topics_arrDb_Unit);
        #echo "DB topics are: ".var_dump($arr_Topics_Db)." \r\n";

        #Get the topic differences between the xml file and the DB
        $topics = array_diff($arr_Topics_Xml, $arr_Topics_Db);
        $topics = implode("|", $topics);
        #echo "%%%%%% Topic differences are:".$topics." \r\n";

        $saveToDb = 0; //set to 0
        $resendAll = 0; //set to 0

        #If the count of topics is not empty then resend the message to the new topic
        if (!empty($topics)) {
            $logger->info("%%%%%% " . $topics . " - New units dispatched - ");
            $saveToDb = 1;
            $resendAll = 0;
        } else {
            $logger->info("No new units - nothing to send");
            #echo "No new units - nothing to send \r\n\r\n";
        }

        #Check to see if the call type changes if so resend to all topics
        // $AgencyContexts_AgencyContext_CallType = $xml->AgencyContexts->AgencyContext[0]->CallType;
        $AgencyContexts_AgencyContext_CallType = $sep = '';
        $nrOfRows = $xml->AgencyContexts->AgencyContext->count();
        $n = 0;
        for ($n = 0; $n < $nrOfRows; $n++) {
            $value = $xml->AgencyContexts->AgencyContext[$n]->CallType;
            $AgencyContexts_AgencyContext_CallType .= $sep . $value;
            $sep = '|';
        }
        if ($AgencyContexts_AgencyContext_CallType != $db_CallType) {
            $logger->info("%%%%%%" . $AgencyContexts_AgencyContext_CallType . " <- " . $db_CallType . " - Call type change");
            $saveToDb = 1;
            $resendAll = 1;
        } else {
            $logger->info("No call type changes - nothing to send");
            #echo "No new call types - nothing to send \r\n\r\n";
        }

        #Check to see if the location changes if so resend to all topics
        #$Location_FullAddress = $xml->Location->FullAddress;
        if ($xml->Location->FullAddress != $db_FullAddress) {
            #var_dump($xml->Location->FullAddress);
            #var_dump($db_FullAddress);
            $logger->info("%%%%%%" . $xml->Location->FullAddress . " <- " . $db_FullAddress . " resend because address change");
            $saveToDb = 1;
            $resendAll = 1;
        } else {
            $logger->info("No new location - nothing to send");
            #echo "No new location - nothing to send \r\n\r\n";
        }

        #Check if Alarm Level changed
        #$AlarmLevel = $xml->AlarmLevel;
        if ($xml->AlarmLevel > $db_AlarmLevel) {
            #var_dump($xml->AlarmLevel);
            #var_dump($db_AlarmLevel);
            $logger->info("%%%%%%" . $xml->AlarmLevel . " <- " . $db_AlarmLevel . " resend because alarm level increased");
            $saveToDb = 1;
            $resendAll = 1;
        } else {
            $logger->info("No new alarm level - nothing to send");
            #echo "No new alarm level - nothing to send \r\n\r\n";
        }

        if ($saveToDb) {
            #Check Delta time
            if ($delta < $TimeAdjust) { // if return true then send
                #var_dump($delta);
                #var_dump($TimeAdjust);
                $logger->info("Time delta is " . $delta . " if less than " . $TimeAdjust . " message will be sent");
                fcn_16_insertRecord($db_conn, $db_incident, $xml, $logger, $agencies, $jurisdictions, $units);
                $logger->info("Passing xml file to fcn_21_sendNtfy");
                fcn_21_sendNtfy($db_conn, $db_incident, $xml, $delta, $logger, $topics, $resendAll); // Ntfy
            } else {
                $logger->info("Time delta is too high " . $delta . " - NOT passing record to Ntfy");
                #echo "Delta too high - nothing to send \r\n\r\n";
                fcn_16_insertRecord($db_conn, $db_incident, $xml, $logger, $agencies, $jurisdictions, $units);
            }
        } else {
            $logger->info("saveToDb flag not set - nothing passed to Ntfy");
        }
    }
}