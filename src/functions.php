<?php

/**
 * createIncidentsTable
 *
 * @param  mixed $db_conn
 * @param  mixed $db_incident
 * @return void
 */
function createIncidentsTable($db_conn, $db_incident)
{
    global $logger;
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
    $logger->info("[CreateIncidentsTable] Create table " . $db_incident . " if it does not exist");
}

/**
 * recordReceived
 *
 * @param  mixed $db_conn
 * @param  mixed $db_incident
 * @param  mixed $strInFile
 * @return void
 */
function recordReceived($db_conn, $db_incident, $strInFile) // called from monitor.php

{
    global $logger;
    $xml = simplexml_load_file($strInFile) or die("Error: Cannot create object");
    $logger->info("[recordReceived] File " . $strInFile . " read into simpleXML");
    if ($xml->ClosedFlag == "true") { //record is closed
        /***** This is where the mlApiRemoveIncident function will get called to remove a existing ML incident *****/
        $logger->info("[recordReceived] ClosedFlag is true so remove record " . $xml->CallId . " from db");
        deleteRecord($db_conn, $db_incident, $xml->CallId);
        return;
    } elseif (!callIdExist($db_conn, $db_incident, $xml->CallId)) { // record does not exist in db
        /***** This is where the mlApiCreateIncident function $mlCreate = 1 will get set to 1 to create a new ml incident *****/
        $logger->info("[recordReceived] New record entered into DB set send = 1");
        /***** Need to add the $mlCreate variable to the insertRecord function below *****/
        insertRecord($db_conn, $db_incident, $xml, $send = 1); // This is where a new record gets entered into db
    } else {
        $logger->info("[recordReceived] Record exists in DB - check for changes to requsite fields");
        /***** Need to add the $mlCreate variable to the insertRecord function below *****/
        insertRecord($db_conn, $db_incident, $xml, $send = 0); // Record exists in DB-check for changes to in fields
    }
}

/**
 * callIdExist
 *
 * @param  mixed $db_conn
 * @param  mixed $db_incident
 * @param  mixed $CallId
 * @return $RowExists (true) if row exists
 */
function callIdExist($db_conn, $db_incident, $CallId)
{
    global $logger;
    $sql = "SELECT count(1) FROM $db_incident WHERE db_CallId = $CallId LIMIT 1";
    $result = $db_conn->query($sql);
    foreach ($result as $result) {
        $RowExists = $result[0];
        $logger->info("[callIdExist] Call id exists ");
    }
    return $RowExists;
}

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
    global $logger;
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
        $logger->info("[insertRecord] " . $AgencyContexts_AgencyContext_CallType . " <- " . $db_CallType . "-Call type change");
        $send = 1;
    }
    $AlarmLevel = $xml->AlarmLevel;
    if ($AlarmLevel > $db_AlarmLevel) {
        #$logger->info("[insertRecord] ".$AlarmLevel." > ".$db_AlarmLevel." resend because alarm level increased");
        #$send = 1;
    }
    $NatureOfCall = $xml->NatureOfCall;
    $Location_CommonName = $xml->Location->CommonName;
    $Location_FullAddress = $xml->Location->FullAddress;
    if ($Location_FullAddress != $db_FullAddress) {
        #$logger->info("[insertRecord] ".$Location_FullAddress." <> ".$db_FullAddress." resend because address change");
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
        #$logger->info("[insertRecord] Resend because unit number was added");
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
    $logger->info("[insertRecord] Record inserted into DB");

    if (sendActiveIncident($db_conn, $db_nwsCfsTableName = "nwscfstypecsv", $AgencyContexts_AgencyContext_CallType)) {
        /***** This is where the mlApiCreateIncident function will be called if ($mlCreate == 1) { mlApiCreateIncident ($v1, $v2, $v3, $v4.....); } will create a new ml incident *****/
        /***** This is where the mlApiAddEndpoint function will be called mlApiAddEndpoint ($v1, $v2, $v3, $v4.....); will add ml endpoint users to incident *****/
        /***** This is where the mlApiAddResource function will be called mlApiAddResource ($v1, $v2, $v3, $v4.....); will add ml resources like radio and phone endpoints *****/
        if ($send == 1) {
            $logger->info("[insertRecord] Sending xml file to push functions");
            sendWebhook($db_conn, $db_incident, $xml); // Webhook
            #sendPushover($db_conn, $db_incident, $xml);  // Pushover
            #sendSNPP($db_conn, $db_incident, $xml);  // Active911
        }
        $logger->info("[insertRecord] Send flag not set - nothing sent to push functions");
    }
}

/**
 * deleteRecord
 *
 * @param  mixed $db_conn
 * @param  mixed $db_incident
 * @param  mixed $CallId
 * @return void
 */
function deleteRecord($db_conn, $db_incident, $CallId)
{
    global $logger;
    $sql = "DELETE FROM $db_incident WHERE db_CallId = $CallId";
    $db_conn->exec($sql);
    $logger->info("[deleteRecord] Delete record " . $CallId . " from table " . $db_incident . "");
}

/**
 * openConnection
 *
 * @param  mixed $db
 * @return $db_conn  DB connection
 */
function openConnection($db)
{
    global $logger;
    $db_conn = new PDO("sqlite:$db");
    $logger->info("[openConnection] Connection opened to database " . $db . "");
    print_r($db_conn);
    return $db_conn;
}

/**
 * closeConnection
 *
 * @param  mixed $db_conn
 * @return void
 */
function closeConnection($db_conn)
{
    global $logger;
    $db_conn = null;
    $logger->info("[closeConnection] Connection closed to database");
}

/**
 * unlinkArchiveOld
 *
 * @param  mixed $path
 * @return void
 */
function unlinkArchiveOld($path) // $strBackupFolder

{
    global $logger;
    if ($handle = opendir($path)) {
        while (false !== ($file = readdir($handle))) {
            $filelastmodified = filemtime($path . "/" . $file);
            //3 days * 24 hours in a day * 3600 seconds per hour
            if ((time() - $filelastmodified) > 3 * 24 * 3600) {
                unlink($path . "/" . $file);
                $logger->info("[unlinkArchiveOld] File " . $file . " removed from " . $path . "");
            }
        }
        closedir($handle);
    }
}

/**
 * tableExists
 *
 * Check if a table exists in the current database.
 * @param PDO $$db_conn PDO instance connected to a database.
 * @param string $$db_incident table to search for.
 * @return bool TRUE if table exists, FALSE if no table found.
 */
function tableExists($db_conn, $db_incident)
{
    global $logger;
    // Try a select statement against the table
    // Run it in try/catch in case PDO is in ERRMODE_EXCEPTION.
    try {
        $result = $db_conn->query("SELECT 1 FROM '$db_incident' LIMIT 1");
    } catch (Exception $e) {
        // We got an exception == table not found
        $logger->info("[tableExists] Table " . $db_incident . " not found");
        return false;
    }
    // Result is either boolean FALSE (no table found) or PDOStatement Object (table found)
    return $result !== false;
}

/**
 * csvToSqlite
 *
 * @param  mixed $db_conn
 * @param  mixed $csvFilePath
 * @param  mixed $options
 * @return void
 */
function csvToSqlite($db_conn, $csvFilePath, $options = array())
{
    global $logger;
    extract($options);
    if (($csv_handle = fopen($csvFilePath, "r")) === false) {
        throw new Exception('Cannot open CSV file');
    }
    $delimiter = ',';
    $table = preg_replace("/[^A-Z0-9]/i", '', basename($csvFilePath));
    $fields = array_map(function ($field) {
        return strtolower(preg_replace("/[^A-Z0-9]/i", '', $field));
    }, fgetcsv($csv_handle, 0, $delimiter));
    $create_fields_str = join(', ', array_map(function ($field) {
        return "$field TEXT NULL";
    }, $fields));
    $db_conn->beginTransaction();
    $create_table_sql = "CREATE TABLE IF NOT EXISTS $table ($create_fields_str)";
    $db_conn->exec($create_table_sql);
    $insert_fields_str = join(', ', $fields);
    $insert_values_str = join(', ', array_fill(0, count($fields), '?'));
    $insert_sql = "INSERT INTO $table ($insert_fields_str) VALUES ($insert_values_str)";
    $insert_sth = $db_conn->prepare($insert_sql);
    $inserted_rows = 0;
    while (($data = fgetcsv($csv_handle, 0, $delimiter)) !== false) {
        $insert_sth->execute($data);
        $inserted_rows++;
    }
    $db_conn->commit();
    fclose($csv_handle);
    $logger->info("[CsvToSqlite] Table " . $table . " was created");
    return array(
        'table' => $table,
        'fields' => $fields,
        'insert' => $insert_sth,
        'inserted_rows' => $inserted_rows,
    );
}

/**
 * sendActiveIncident
 *
 * @param  mixed $db_conn
 * @param  mixed $db_nwsCfsTableName
 * @param  mixed $IncidentType
 * @return true | false
 */
function sendActiveIncident($db_conn, $db_nwsCfsTableName, $IncidentType)
{
    global $logger;
    if (strpos($IncidentType, "|")) { // two incident types exist
        $type = explode("|", $IncidentType);
        $sql = "SELECT * FROM $db_nwsCfsTableName WHERE cfstype LIKE '$type[0]' OR cfstype LIKE '$type[1]'";
    } else {
        $sql = "SELECT * FROM $db_nwsCfsTableName WHERE cfstype LIKE '$IncidentType'";
    }
    $result = $db_conn->query($sql);
    $n = 0;
    foreach ($result as $row) {
        //echo "Incident Type: " . $row['cfstype'] . "\n";
        //echo "Default Status: " . $row['defaultstatus'] . "\n";
        //echo "Police: " . $row['police'] . "\n";
        //echo "Fire: " . $row['fire'] . "\n";
        //echo "EMS: " . $row['ems'] . "\n";
        //echo "Active: " . $row['active'] . "\n";
        if ($row['active'] == "Yes") {
            $n++;
        }
    }
    if ($n >= 1) {
        $logger->info("[sendActiveIncident] Incident is whitelisted");
        return true;
    } else {
        $logger->info("[sendActiveIncident] Incident is NOT whitelisted");
        return false;
    }
}

/**
 * sendSNPP
 *
 * @param  mixed $db_conn
 * @param  mixed $db_incident
 * @param  mixed $xml
 * @return void
 */
function sendSNPP($db_conn, $db_incident, $xml)
{
    global $logger;
    global $snpp_url;
    global $snpp_port;
    global $snpp_page;
    $CallId = $xml->CallId;
    $sql = "SELECT * FROM $db_incident WHERE db_CallId = '$CallId'";
    $row = $db_conn->prepare($sql);
    $row->execute();
    $snpp_mess = $row->fetchAll(PDO::FETCH_ASSOC);
    $snpp = fsockopen($snpp_url, $snpp_port, $errno, $errstr);
    if (!$snpp) {
        $logger->info("[sendSNPP] fsockopen error - " . $errstr($errno) . "");
    } else {
        $logger->info("[sendSNPP] Open connection to Active911 - " . fgets($snpp) . "");
        fwrite($snpp, "PAGE $snpp_page\r\n");
        $logger->info("[sendSNPP] Execute PAGEr number to " . $snpp_page . " - " . fgets($snpp) . "");
        fwrite($snpp, "DATA\r\n");
        $logger->info("[sendSNPP] Set DATA protocol - " . fgets($snpp) . "");
        $out = $sep = '';
        foreach ($snpp_mess[0] as $key => $value) {
            $out .= $sep . $key . ":" . $value . "\n";
            $sep = '';
        }
        fwrite($snpp, "$out\r\n");
        fwrite($snpp, ".\r\n");
        $logger->info("[sendSNPP] \n" . $out . "");
        $logger->info("[sendSNPP] " . fgets($snpp) . "");
        fwrite($snpp, "SEND\r\n");
        $logger->info("[sendSNPP] Execute SEND - " . fgets($snpp) . "");
        fwrite($snpp, "QUIT\r\n");
        $logger->info("[sendSNPP] Execute QUIT - " . fgets($snpp) . "");
        fclose($snpp);
    }
}

/**
 * sendPushover
 *
 * @param  mixed $db_conn
 * @param  mixed $db_incident
 * @param  mixed $xml
 * @return void
 */
function sendPushover($db_conn, $db_incident, $xml)
{
    global $logger;
    global $pushoverUrl;
    global $pushoverToken;
    global $pushoverUser;
    global $googleApiKey;
    $CallId = $xml->CallId;
    $sql = "SELECT * FROM $db_incident WHERE db_CallId = '$CallId'";
    $row = $db_conn->prepare($sql);
    $row->execute();
    $pushoverMessage = $row->fetchAll(PDO::FETCH_ASSOC);
    $out = $sep = '';
    foreach ($pushoverMessage[0] as $key => $value) {
        $out .= $sep . $key . ":" . $value . "\n";
        $sep = '';
    }
    extract($pushoverMessage[0]);
    $urlEncFullAddress = urlencode($db_FullAddress);
    $mapUrl = "https://maps.googleapis.com/maps/api/staticmap?center=$db_LatitudeY,$db_LongitudeX&zoom=16&size=400x400&
    maptype=hybrid&&markers=color:green|label:$urlEncFullAddress%7C$db_LatitudeY,$db_LongitudeX&key=$googleApiKey";
    $logger->info("[sendPushover] Open connection to Pushover using Google Url - \n" . $mapUrl . "");
    curl_setopt_array($ch = curl_init(), array(
        CURLOPT_URL => "$pushoverUrl",
        CURLOPT_POSTFIELDS => array(
            "token" => "$pushoverToken",
            "user" => "$pushoverUser",
            "title" => "MCCD Call: $db_CallNumber $db_CallType",
            "message" => "
            Type: $db_AgencyType
            Loc: $db_FullAddress
            Inc: $db_CallType
            Nature: $db_NatureOfCall
            Cross Rd: $db_NearestCrossStreets
            Beat: $db_PoliceBeat
            Quad: $db_FireQuadrant
            Unit: $db_UnitNumber
            Narr: $db_Narrative_Text",
            "sound" => "bike",
            "html" => "1",
            "attachment" => curl_file_create("$mapUrl", "image/jpeg"),
        ),
    ));
    $result = curl_exec($ch);
    curl_close($ch);
    $logger->info("[sendPushover] Pushover message sent - " . $result . "");
}

/**
 * sendWebhook
 *
 * @param  mixed $db_conn
 * @param  mixed $db_incident
 * @param  mixed $xml
 * @return void
 */
function sendWebhook($db_conn, $db_incident, $xml)
{
    global $logger;
    global $webhookUrl;
    global $googleApiKey;
    $CallId = $xml->CallId;
    $sql = "SELECT * FROM $db_incident WHERE db_CallId = '$CallId'";
    $row = $db_conn->prepare($sql);
    $row->execute();
    $webhookMessage = $row->fetchAll(PDO::FETCH_ASSOC);
    $out = $sep = '';
    foreach ($webhookMessage[0] as $key => $value) {
        $out .= $sep . $key . ":" . $value . "\n";
        $sep = '';
    }
    extract($webhookMessage[0]);
    $urlEncFullAddress = urlencode($db_FullAddress);
    $mapUrl = "https://maps.googleapis.com/maps/api/staticmap?center=$db_LatitudeY,$db_LongitudeX&zoom=16&size=800x800&
    maptype=hybrid&&markers=color:green|label:$urlEncFullAddress%7C$db_LatitudeY,$db_LongitudeX&key=$googleApiKey";
    $logger->info("[sendPushover] Open connection to Webhook");

    // create connector instance
    $connector = new \Sebbmyr\Teams\TeamsConnector($webhookUrl);
    // create a custom card
    $card = new \Sebbmyr\Teams\Cards\CustomCard('' . $db_CallNumber . ' ' . $db_CallType, '' . $db_FullAddress . '');
    // add information
    $card->setColor('01BC36')
        ->addFacts($db_CommonName, ['Nature of Call:' => $db_NatureOfCall, 'Narrative:' => $db_Narrative_Text, 'Units:' => $db_UnitNumber, 'Fire Quad:' => $db_FireQuadrant, 'Cross Street:' => $db_NearestCrossStreets]);
    // send card via connector
    $connector->send($card);

    $logger->info("[sendWebhook] Webhook message sent");
}

/**
 * unlinkLogFiles
 *
 * @param  mixed $strLogFolder
 * @return void
 */
function unlinkLogFiles($strLogFolder)
{
    global $logger;
    $files = glob("$strLogFolder/*.log");
    $now = time();
    foreach ($files as $file) {
        if (is_file($file)) {
            if ($now - filemtime($file) >= 60 * 60 * 24 * 2) { // 2 days
                unlink($file);
                $logger->info("[unlinkLogFiles] Log " . $file . " removed from " . $strLogFolder . "");
            }
        }
    }
}
