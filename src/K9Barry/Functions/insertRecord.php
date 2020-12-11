<?php

/**
 * insertRecord
 *
 * @param  mixed $db_conn
 * @param  mixed $db_incident
 * @param  mixed $xml
 * @param  mixed $send
 ***** Need to add the $mlCreate variable to the insertRecord function below *****
 * @return void
 */
function insertRecord($db_conn, $db_incident, $xml, $send)
{
    global $logger, $CfsTableName, $webhookSend, $pushoverSend, $snppSend;
    if ($send == 0) { // checking for changes between old and new
        $sql = "SELECT * FROM $db_incident WHERE db_CallId = '$xml->CallId'";
        $row = $db_conn->prepare($sql);
        $row->execute();
        $dbInfo = $row->fetchAll(PDO::FETCH_ASSOC);
        extract($dbInfo[0]); // db info
    } else {
        $db_CallType = "";
        $db_AlarmLevel = "";
        $db_FullAddress = "";
        $db_UnitNumber = "";
    }
    $CallId = $xml->CallId;
    $CallNumber = $xml->CallNumber;
    $ClosedFlag = $xml->ClosedFlag;
    // $AgencyContexts_AgencyContext_AgencyType = $xml->AgencyContexts->AgencyContext[0]->AgencyType;
    $AgencyContexts_AgencyContext_AgencyType = $sep = '';
    $nrOfRows = $xml->AgencyContexts->AgencyContext->count();
    $n = 0;
    for ($n = 0; $n < $nrOfRows; $n++) {
        $value = $xml->AgencyContexts->AgencyContext[$n]->AgencyType;
        $AgencyContexts_AgencyContext_AgencyType .= $sep . $value;
        $sep = '|';
    }
    $CreateDateTime = $xml->CreateDateTime;
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
        $logger->info("" . $AgencyContexts_AgencyContext_CallType . " <- " . $db_CallType . "-Call type change");
        $send = 1;
    }
    $AlarmLevel = $xml->AlarmLevel;
    if ($AlarmLevel > $db_AlarmLevel) {
        #$logger->info("".$AlarmLevel." > ".$db_AlarmLevel." resend because alarm level increased");
        #$send = 1;
    }
    $NatureOfCall = $xml->NatureOfCall;
    $Location_CommonName = $xml->Location->CommonName;
    $Location_FullAddress = $xml->Location->FullAddress;
    if ($Location_FullAddress != $db_FullAddress) {
        #$logger->info("".$Location_FullAddress." <> ".$db_FullAddress." resend because address change");
        #$send = 1;
    }
    $Location_State = $xml->Location->State;
    $Location_NearestCrossStreets = $xml->Location->NearestCrossStreets;
    $Location_AdditionalInfo = $xml->Location->AdditionalInfo;
    $Location_FireOri = $xml->Location->FireOri;
    $Location_FireQuadrant = $xml->Location->FireQuadrant;
    $Location_PoliceOri = $xml->Location->PoliceOri;
    $Location_PoliceBeat = $xml->Location->PoliceBeat;
    $Location_LatitudeY = $xml->Location->LatitudeY;
    $Location_LongitudeX = $xml->Location->LongitudeX;
    // $AssignedUnits_Unit_UnitNumber = $xml->AssignedUnits->Unit->UnitNumber;
    $arr_db_UnitNumber = array_filter(explode('|', $db_UnitNumber));
    $str_xml_UnitNumber = $sep = '';
    $nrOfRows = $xml->AssignedUnits->Unit->count();
    $n = 0;
    for ($n = 0; $n < $nrOfRows; $n++) {
        $value = $xml->AssignedUnits->Unit[$n]->UnitNumber;
        $str_xml_UnitNumber .= $sep . $value;
        $sep = '|';
    }
    $arr_xml_UnitNumber = array_filter(explode('|', $str_xml_UnitNumber));

    if ($arr_db_UnitNumber != $arr_xml_UnitNumber) {
        #$logger->info("Resend because unit number was added");
        #$send = 1;
    }
    $merge_arr_UnitNumber = array_merge_recursive($arr_db_UnitNumber, $arr_xml_UnitNumber);
    $merge_arr_UnitNumber = array_unique($merge_arr_UnitNumber);
    $merge_arr_UnitNumber = array_values($merge_arr_UnitNumber); // resort key values in array
    $out = $sep = '';
    $nrOfRows = count($merge_arr_UnitNumber);
    $n = 0;
    for ($n = 0; $n < $nrOfRows; $n++) {
        $value = $merge_arr_UnitNumber[$n];
        $out .= $sep . $value;
        $sep = '|';
    }
    $AssignedUnits_Unit_UnitNumber = $out;
    $RadioChannel = preg_grep('/FG-[1-9]/m', $merge_arr_UnitNumber);
    $RadioChannel = implode(" ", $RadioChannel);
    // $Incidents_Incident_Number = $xml->Incidents->Incident->Number;
    $Incidents_Incident_Number = $sep = '';
    $nrOfRows = $xml->Incidents->Incident->count();
    $n = 0;
    for ($n = 0; $n < $nrOfRows; $n++) {
        $value = $xml->Incidents->Incident[$n]->Number;
        $Incidents_Incident_Number .= $sep . $value;
        $sep = '|';
    }
    // $Incidents_Incident_Jurisdiction = $xml->Incidents->Incident->Jurisdiction;
    $Incidents_Incident_Jurisdiction = $sep = '';
    $nrOfRows = $xml->Incidents->Incident->count();
    $n = 0;
    for ($n = 0; $n < $nrOfRows; $n++) {
        $value = $xml->Incidents->Incident[$n]->Jurisdiction;
        $Incidents_Incident_Jurisdiction .= $sep . $value;
        $sep = '|';
    }
    // $Narratives_Narrative_Text = $xml->Narratives->Narrative->Text;
    $Narratives_Narrative_Text = $sep = '';
    $nrOfRows = $xml->Narratives->Narrative->count();
    $n = 0;
    for ($n = 0; $n < $nrOfRows; $n++) {
        $value = $xml->Narratives->Narrative[$n]->Text;
        $Narratives_Narrative_Text .= $sep . $value;
        $sep = '|';
    }
    // Cleanup values before inserting by replacing ' with ''
    $AgencyContexts_AgencyContext_CallType = trim(str_replace("'", "''", $AgencyContexts_AgencyContext_CallType));
    $NatureOfCall = trim(strtoupper(str_replace("'", "''", $NatureOfCall)));
    $Location_CommonName = trim(str_replace("'", "''", $Location_CommonName));
    $Location_FullAddress = trim(str_replace("'", "''", $Location_FullAddress));
    $Location_NearestCrossStreets = trim(str_replace("'", "''", $Location_NearestCrossStreets));
    $Location_AdditionalInfo = trim(str_replace("'", "''", $Location_AdditionalInfo));
    // replace \r \n \t with ' ' then make uppercase and trim
    $Narratives_Narrative_Text = trim(strtoupper(preg_replace(array("/\s\s+/", "/[\t\n\r]/"), ' ', str_replace("'", "''", $Narratives_Narrative_Text))));

    $sql = "INSERT OR REPLACE INTO $db_incident
        (
        db_CallId,
        db_CallNumber,
        db_ClosedFlag,
        db_AgencyType,
        db_CreateDateTime,
        db_CallType,
        db_AlarmLevel,
        db_RadioChannel,
        db_NatureOfCall,
        db_CommonName,
        db_FullAddress,
        db_State,
        db_NearestCrossStreets,
        db_AdditionalInfo,
        db_FireOri,
        db_FireQuadrant,
        db_PoliceOri,
        db_PoliceBeat,
        db_LatitudeY,
        db_LongitudeX,
        db_UnitNumber,
        db_Incident_Number,
        db_Incident_Jurisdiction,
        db_Narrative_Text
        )
        VALUES
        (
        '$CallId',
        '$CallNumber',
        '$ClosedFlag',
        '$AgencyContexts_AgencyContext_AgencyType',
        '$CreateDateTime',
        '$AgencyContexts_AgencyContext_CallType',
        '$AlarmLevel',
        '$RadioChannel',
        '$NatureOfCall',
        '$Location_CommonName',
        '$Location_FullAddress',
        '$Location_State',
        '$Location_NearestCrossStreets',
        '$Location_AdditionalInfo',
        '$Location_FireOri',
        '$Location_FireQuadrant',
        '$Location_PoliceOri',
        '$Location_PoliceBeat',
        '$Location_LatitudeY',
        '$Location_LongitudeX',
        '$AssignedUnits_Unit_UnitNumber',
        '$Incidents_Incident_Number',
        '$Incidents_Incident_Jurisdiction',
        '$Narratives_Narrative_Text'
        )";
    $db_conn->exec($sql);
    $logger->info("Record inserted into DB");

    if (fcn_TimeOver15Minutes($CreateDateTime)) { // if return true then do not send
        $send = 0;
    } else {
        $send = 1; // Send the incident to the endpoints
    }

    if (sendActiveIncident($db_conn, $CfsTableName, $AgencyContexts_AgencyContext_CallType)) {
        if ($send == 1) {
            if ($webhookSend) {
                $logger->info("Sending xml file to webhook");
                sendWebhook($db_conn, $db_incident, $xml); // Webhook
            }
            if ($pushoverSend) {
                $logger->info("Sending xml file to pushover");
                sendPushover($db_conn, $db_incident, $xml); // Pushover
            }
            if ($snppSend) {
                $logger->info("Sending xml file to snpp");
                sendSNPP($db_conn, $db_incident, $xml); // Active911 via snpp
            }
        } else {
            $logger->info("Send flag not set - nothing sent to endpoint(s)");
        }
    }
}
