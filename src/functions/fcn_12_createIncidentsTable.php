<?php

/**
 * fcn_12_createIncidentsTable
 * 
 * Creates the incidents table in the SQLite database if it doesn't exist.
 * Defines the complete schema for storing New World CAD incident data including
 * location, timing, agency information, and narrative details.
 *
 * @param mixed $db_conn Database connection (PDO instance)
 * @param string $db_incident Database table name to create
 * @param mixed $logger Logger instance for database schema operations
 * @return void
 */
function fcn_12_createIncidentsTable(mixed $db_conn, string $db_incident, mixed $logger): void
{
    $sql = "CREATE TABLE IF NOT EXISTS $db_incident
		(
        db_CallId INTEGER PRIMARY KEY,
        db_CallNumber INTEGER,
        db_ClosedFlag TEXT,
        db_AgencyType TEXT,
        db_CreateDateTime TEXT,
        db_CallType TEXT,
        db_AlarmLevel TEXT,
        db_RadioChannel TEXT,
        db_NatureOfCall TEXT,
        db_CommonName TEXT,
        db_FullAddress TEXT,
        db_State TEXT,
        db_NearestCrossStreets TEXT,
        db_AdditionalInfo TEXT,
        db_FireOri TEXT,
        db_FireQuadrant TEXT,
        db_PoliceOri TEXT,
        db_PoliceBeat TEXT,
        db_LatitudeY TEXT,
        db_LongitudeX TEXT,
        db_UnitNumber TEXT,
        db_Incident_Number TEXT,
        db_Incident_Jurisdiction TEXT,
        db_Narrative_Text TEXT
        )";
    $db_conn->exec($sql);
    $logger->info("[fcn_12_CreateIncidentsTable] Create table " . $db_incident . " if it does not exist");
}
