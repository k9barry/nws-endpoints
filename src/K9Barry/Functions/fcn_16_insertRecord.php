<?php

/**
 * fcn_16_insertRecord
 *
 * @param  mixed $db_conn
 * @param  mixed $db_incident
 * @param  mixed $xml
 * @param  mixed $send
 * @param  mixed $logger
 * @return void
 */
function fcn_16_insertRecord($db_conn, $db_incident, $xml, $send, $logger)
{
    global $CfsTableName, $ntfySend, $TimeAdjust;
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
        $delta = "";
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

    $delta = fcn_20_DeltaTime($CreateDateTime);

    if ($delta < $TimeAdjust) { // if return true then send
        $logger->info("Time delta is ".$delta." passing record to see if whitelisted at fcn_sendActiveIncident");       
        if ($send == 1) {
            if ($ntfySend) {
                $logger->info("Passing xml file to fcn_21_sendNtfy");
                fcn_21_sendNtfy($db_conn, $db_incident, $xml, $delta, $logger); // Ntfy
            }
        } else {
            $logger->info("Send flag not set - nothing passed to endpoint(s)");
        }
    } else {
        $logger->info("Time delta is too high ".$delta." - NOT passing record to endpoint(s)");
    }
}
