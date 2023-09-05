<?php

/**
 * fcn_16_insertRecord
 *
 * @param  mixed $db_conn
 * @param  mixed $db_incident
 * @param  mixed $xml
 * @param  mixed $send
 * @param  mixed $logger
 * @param  mixed $agencies
 * @param  mixed $jurisdictions
 * @param  mixed $units
 * @return void
 */
function fcn_16_insertRecord($db_conn, $db_incident, $xml, $logger, $agencies, $jurisdictions, $units) {
    global $CfsTableName;
    /*if ($send == 0) { // checking for changes between old and new
        $sql = "SELECT * FROM $db_incident WHERE db_CallId = '$xml->CallId'";
        $row = $db_conn->prepare($sql);
        $row->execute();
        $dbInfo = $row->fetchAll(PDO::FETCH_ASSOC);
        extract($dbInfo[0]); // db info
    } else {
        $db_CallType = "";
        $db_AlarmLevel = "";
        $db_FullAddress = "";
        $db_AgencyType = "";
        $db_UnitNumber = "";
        $delta = "";
    }*/
    $CallId = $xml->CallId;
    $CallNumber = $xml->CallNumber;
    $ClosedFlag = $xml->ClosedFlag;
// $AgencyContexts_AgencyContext_AgencyType = $xml->AgencyContexts->AgencyContext[0]->AgencyType;
    $AgencyContexts_AgencyContext_AgencyType = $agencies;
// Create Date and Time
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
    $AlarmLevel = $xml->AlarmLevel;
    $NatureOfCall = $xml->NatureOfCall;
    $Location_CommonName = $xml->Location->CommonName;
    $Location_FullAddress = $xml->Location->FullAddress;
    $Location_State = $xml->Location->State;
    $Location_NearestCrossStreets = $xml->Location->NearestCrossStreets;
    $Location_AdditionalInfo = $xml->Location->AdditionalInfo;
    $Location_FireOri = $xml->Location->FireOri;
    $Location_FireQuadrant = $xml->Location->FireQuadrant;
    $Location_PoliceOri = $xml->Location->PoliceOri;
    $Location_PoliceBeat = $xml->Location->PoliceBeat;
    $Location_LatitudeY = $xml->Location->LatitudeY;
    $Location_LongitudeX = $xml->Location->LongitudeX;

// $Incidents_Incident_Jurisdiction = $xml->Incidents->Incident->Jurisdiction;
    $Incidents_Incident_Jurisdiction = $jurisdictions;
    $nrOfRows = $xml->Incidents->Incident->count();
    $n = 0;
    for ($n = 0; $n < $nrOfRows; $n++) {
        $value = $xml->Incidents->Incident[$n]->Jurisdiction;
        $Incidents_Incident_Jurisdiction .= $sep . $value;
        $sep = '|';
    }   
// $AssignedUnits_Unit_UnitNumber = $xml->AssignedUnits->Unit->UnitNumber;
    $AssignedUnits_Unit_UnitNumber = $units;
    $RadioChannel = preg_match('/FG-[1-9]/m', $AssignedUnits_Unit_UnitNumber, $match);
    $RadioChannel = implode("|", $match);
    // $Incidents_Incident_Number = $xml->Incidents->Incident->Number;
    $Incidents_Incident_Number = $sep = '';
    $nrOfRows = $xml->Incidents->Incident->count();
    $n = 0;
    for ($n = 0; $n < $nrOfRows; $n++) {
        $value = $xml->Incidents->Incident[$n]->Number;
        $Incidents_Incident_Number .= $sep . $value;
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
}