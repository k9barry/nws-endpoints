<?php

use Psr\Log\LoggerInterface;

/**
 * fcn_16_insertRecord
 * 
 * Inserts or updates incident record in the database from New World CAD XML data.
 * Parses XML fields, cleans data, and stores complete incident information for
 * notification processing and historical tracking.
 *
 * @param PDO $db_conn Database connection (PDO instance)
 * @param string $db_incident Database table name for incident records
 * @param SimpleXMLElement $xml XML object containing New World CAD incident data
 * @param LoggerInterface $logger Logger instance for database operations
 * @param string $agencies Agency information for incident categorization
 * @param string $jurisdictions Jurisdiction data for incident routing
 * @param string $units Unit information for incident response
 * @return void
 * @throws PDOException When database insertion fails
 * @throws InvalidArgumentException When XML data is invalid
 */
function fcn_16_insertRecord(PDO $db_conn, string $db_incident, SimpleXMLElement $xml, LoggerInterface $logger, string $agencies, string $jurisdictions, string $units): void
{
    try {
        // Extract and validate required XML fields
        $CallId = (string) $xml->CallId;
        $CallNumber = (string) $xml->CallNumber;
        $ClosedFlag = (string) $xml->ClosedFlag;
        
        if (empty($CallId)) {
            throw new InvalidArgumentException("CallId is required but not found in XML");
        }

        $AgencyContexts_AgencyContext_AgencyType = $agencies;
        
        // Create Date and Time
        $CreateDateTime = (string) $xml->CreateDateTime;
        
        // Extract call types from all agency contexts
        $AgencyContexts_AgencyContext_CallType = '';
        $sep = '';
        if (isset($xml->AgencyContexts->AgencyContext) && $xml->AgencyContexts->AgencyContext->count() > 0) {
            for ($n = 0; $n < $xml->AgencyContexts->AgencyContext->count(); $n++) {
                $value = (string) $xml->AgencyContexts->AgencyContext[$n]->CallType;
                $AgencyContexts_AgencyContext_CallType .= $sep . $value;
                $sep = '|';
            }
        }
        
        // Extract location and incident details with null coalescing
        $AlarmLevel = (string) ($xml->AlarmLevel ?? '');
        $NatureOfCall = (string) ($xml->NatureOfCall ?? '');
        $Location_CommonName = (string) ($xml->Location->CommonName ?? '');
        $Location_FullAddress = (string) ($xml->Location->FullAddress ?? '');
        $Location_State = (string) ($xml->Location->State ?? '');
        $Location_NearestCrossStreets = (string) ($xml->Location->NearestCrossStreets ?? '');
        $Location_AdditionalInfo = (string) ($xml->Location->AdditionalInfo ?? '');
        $Location_FireOri = (string) ($xml->Location->FireOri ?? '');
        $Location_FireQuadrant = (string) ($xml->Location->FireQuadrant ?? '');
        $Location_PoliceOri = (string) ($xml->Location->PoliceOri ?? '');
        $Location_PoliceBeat = (string) ($xml->Location->PoliceBeat ?? '');
        $Location_LatitudeY = (string) ($xml->Location->LatitudeY ?? '');
        $Location_LongitudeX = (string) ($xml->Location->LongitudeX ?? '');

        $Incidents_Incident_Jurisdiction = $jurisdictions;
        
        // Extract assigned units and radio channel
        $AssignedUnits_Unit_UnitNumber = $units;
        $RadioChannel = '';
        if (preg_match('/FG-[1-9]/m', $AssignedUnits_Unit_UnitNumber, $match)) {
            $RadioChannel = implode("|", $match);
        }
        
        // Extract incident numbers
        $Incidents_Incident_Number = '';
        $sep = '';
        if (isset($xml->Incidents->Incident) && $xml->Incidents->Incident->count() > 0) {
            for ($n = 0; $n < $xml->Incidents->Incident->count(); $n++) {
                $value = (string) $xml->Incidents->Incident[$n]->Number;
                $Incidents_Incident_Number .= $sep . $value;
                $sep = '|';
            }
        }
        
        // Extract narrative text
        $Narratives_Narrative_Text = '';
        $sep = '';
        if (isset($xml->Narratives->Narrative) && $xml->Narratives->Narrative->count() > 0) {
            for ($n = 0; $n < $xml->Narratives->Narrative->count(); $n++) {
                $value = (string) $xml->Narratives->Narrative[$n]->Text;
                $Narratives_Narrative_Text .= $sep . $value;
                $sep = '|';
            }
        }
        
        // Clean and sanitize data
        $AgencyContexts_AgencyContext_CallType = trim(str_replace("'", "''", $AgencyContexts_AgencyContext_CallType));
        $NatureOfCall = trim(strtoupper(str_replace("'", "''", $NatureOfCall)));
        $Location_CommonName = trim(str_replace("'", "''", $Location_CommonName));
        $Location_FullAddress = trim(str_replace("'", "''", $Location_FullAddress));
        $Location_NearestCrossStreets = trim(str_replace("'", "''", $Location_NearestCrossStreets));
        $Location_AdditionalInfo = trim(str_replace("'", "''", $Location_AdditionalInfo));
        
        // Clean narrative text: remove extra whitespace, normalize, and escape quotes
        $Narratives_Narrative_Text = trim(strtoupper(preg_replace(
            ["/\s\s+/", "/[\t\n\r]/"], 
            ' ', 
            str_replace("'", "''", $Narratives_Narrative_Text)
        )));

        // Insert record using prepared statement
        $sql = "INSERT OR REPLACE INTO {$db_incident} (
            db_CallId, db_CallNumber, db_ClosedFlag, db_AgencyType, db_CreateDateTime,
            db_CallType, db_AlarmLevel, db_RadioChannel, db_NatureOfCall, db_CommonName,
            db_FullAddress, db_State, db_NearestCrossStreets, db_AdditionalInfo,
            db_FireOri, db_FireQuadrant, db_PoliceOri, db_PoliceBeat,
            db_LatitudeY, db_LongitudeX, db_UnitNumber, db_Incident_Number,
            db_Incident_Jurisdiction, db_Narrative_Text
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $db_conn->prepare($sql);
        $result = $stmt->execute([
            $CallId, $CallNumber, $ClosedFlag, $AgencyContexts_AgencyContext_AgencyType, $CreateDateTime,
            $AgencyContexts_AgencyContext_CallType, $AlarmLevel, $RadioChannel, $NatureOfCall, $Location_CommonName,
            $Location_FullAddress, $Location_State, $Location_NearestCrossStreets, $Location_AdditionalInfo,
            $Location_FireOri, $Location_FireQuadrant, $Location_PoliceOri, $Location_PoliceBeat,
            $Location_LatitudeY, $Location_LongitudeX, $AssignedUnits_Unit_UnitNumber, $Incidents_Incident_Number,
            $Incidents_Incident_Jurisdiction, $Narratives_Narrative_Text
        ]);
        
        if ($result) {
            $logger->info("Record successfully inserted/updated for CallId: {$CallId}");
        } else {
            throw new PDOException("Failed to insert record for CallId: {$CallId}");
        }
        
    } catch (PDOException $e) {
        $logger->error("Database error in fcn_16_insertRecord for CallId {$CallId}: " . $e->getMessage());
        throw $e;
    } catch (Exception $e) {
        $logger->error("Error in fcn_16_insertRecord for CallId {$CallId}: " . $e->getMessage());
        throw new RuntimeException("Failed to insert incident record: " . $e->getMessage(), 0, $e);
    }
}