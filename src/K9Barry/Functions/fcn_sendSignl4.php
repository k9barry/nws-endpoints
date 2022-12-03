<?php

/**
 * fcn_sendSignl4
 *
 * @param  mixed $db_conn
 * @param  mixed $db_incident
 * @param  mixed $xml
 * @param  mixed $delta
 * @param  mixed $logger
 * @return void
 */
function fcn_sendSignl4($db_conn, $db_incident, $xml, $delta, $logger)
{
    global $signl4Url, $signl4Token;
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

    // User cURL
    $Url = "" . $signl4Url . "/" . $signl4Token ."";
    $ch = curl_init($Url);

    //Alert Data
    $s4data = array(
        "Title"=>"$db_CallType",
        "Message"=>"$db_FullAddress",
        #"X-S4-Service"=>"$db_AgencyType",
        "X-S4-Service"=>"Fire",
        "X-S4-Location"=>"$db_LatitudeY, $db_LongitudeX",  #'latitude','longitude'
        "X-S4-Filtering"=>"False",  #True|False If set to true, the event will only trigger a notification to the team, if it contains at least one keyword from one of your services
        #"X-S4-AlertingScenario"=>"single_ack",  #single_ack|multi_ack|emergency
        "X-S4-ExternalID"=>"$db_CallId",  #Record ID from 3rd party system - possibly incident number
        "X-S4-Status"=>"new",  #new|acknowledged|resolved
        "Common Name"=>"$db_CommonName",
        "TalkGroup"=>"$db_RadioChannel",
        "Beat"=>"$db_PoliceBeat",
        "Quad"=>"$db_FireQuadrant",
        "Unit"=>"$db_UnitNumber",
        "Delta"=>"$delta"
    );
    
    $jsonData = json_encode($s4data);
    $logger->info("Open connection to Signl4");

    // Send the request
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // No echo for curl_errno
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json')); 
    
    //Execute the request
    $result = curl_exec($ch);

    if (!curl_errno($ch)) {
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($http_code == 201) {
            // Success
            $logger->info("Signl4 message sent - " . $result ."");
        }
        else {
            // Error
            $logger->error("Error: " . $http_code ."");
        }
    }
    else {
        // Error
        $logger->error("Error");
    }

    curl_close($ch);

}
