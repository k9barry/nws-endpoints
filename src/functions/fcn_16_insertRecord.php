<?php

/**
 * fcn_16_insertRecord
 * 
 * Inserts or updates incident record in the database from New World CAD XML data.
 * Parses XML fields, cleans data, and stores complete incident information for
 * notification processing and historical tracking.
 *
 * @param mixed $db_conn Database connection (PDO instance)
 * @param string $db_incident Database table name for incident records
 * @param mixed $xml XML object containing New World CAD incident data
 * @param mixed $logger Logger instance for database operations
 * @param mixed $agencies Agency information for incident categorization
 * @param mixed $jurisdictions Jurisdiction data for incident routing
 * @param mixed $units Unit information for incident response
 * @return void
 */
function fcn_16_insertRecord(mixed $db_conn, string $db_incident, mixed $xml, mixed $logger, mixed $agencies, mixed $jurisdictions, mixed $units): void
{
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

    $Incidents_Incident_Jurisdiction = $jurisdictions;
    $sep = '';
    $nrOfRows = $xml->Incidents->Incident->count();
    for ($n = 0; $n < $nrOfRows; $n++) {
        $value = $xml->Incidents->Incident[$n]->Jurisdiction;
        $Incidents_Incident_Jurisdiction .= $sep . $value;
        $sep = '|';
    }
    $AssignedUnits_Unit_UnitNumber = $units;
    $RadioChannel = preg_match('/FG-[1-9]/m', $AssignedUnits_Unit_UnitNumber, $match);
    $RadioChannel = implode("|", $match);
    $Incidents_Incident_Number = $sep = '';
    $nrOfRows = $xml->Incidents->Incident->count();
    for ($n = 0; $n < $nrOfRows; $n++) {
        $value = $xml->Incidents->Incident[$n]->Number;
        $Incidents_Incident_Number .= $sep . $value;
        $sep = '|';
    }
    $Narratives_Narrative_Text = $sep = '';
    $nrOfRows = $xml->Narratives->Narrative->count();
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
        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $db_conn->prepare($sql);
    $stmt->execute([
        $CallId,
        $CallNumber,
        $ClosedFlag,
        $AgencyContexts_AgencyContext_AgencyType,
        $CreateDateTime,
        $AgencyContexts_AgencyContext_CallType,
        $AlarmLevel,
        $RadioChannel,
        $NatureOfCall,
        $Location_CommonName,
        $Location_FullAddress,
        $Location_State,
        $Location_NearestCrossStreets,
        $Location_AdditionalInfo,
        $Location_FireOri,
        $Location_FireQuadrant,
        $Location_PoliceOri,
        $Location_PoliceBeat,
        $Location_LatitudeY,
        $Location_LongitudeX,
        $AssignedUnits_Unit_UnitNumber,
        $Incidents_Incident_Number,
        $Incidents_Incident_Jurisdiction,
        $Narratives_Narrative_Text
    ]);
    $logger->info("Record inserted into DB");
}
