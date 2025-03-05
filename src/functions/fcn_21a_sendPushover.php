<?php

/**
 * fcn_21a_sendPushover
 *
 * @param  mixed $db_conn
 * @param  mixed $db_incident
 * @param  mixed $xml
 * @param  mixed $delta
 * @param  mixed $logger
 * @return void
 */


function fcn_21a_sendPushover(mixed $db_conn, mixed $db_incident, mixed $xml, mixed $delta, mixed $logger): void
{
    global $pushoverUrl, $pushoverToken, $pushoverUser;
    $CallId = $xml->CallId;
    $sql = "SELECT * FROM $db_incident WHERE db_CallId = '$CallId'";
    $row = $db_conn->prepare($sql);
    $row->execute();
    $pushoverMessage = $row->fetchAll(PDO::FETCH_ASSOC);
    $out = '';
    foreach ($pushoverMessage[0] as $key => $value) {
        $out .= $key . ":" . $value . "\n";
    }
    extract($pushoverMessage[0]);

    $mapUrl = "https://www.google.com/maps/dir/?api=1&destination=$db_LatitudeY,$db_LongitudeX";

    $logger->info("Open connection to Pushover using Google Url " . $mapUrl);

    curl_setopt_array($ch = curl_init(), array(
        CURLOPT_URL => "$pushoverUrl",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => array(
            "token" => "$pushoverToken",
            "user" => "$pushoverUser",
            "title" => "MCCD Call: $db_CallNumber $db_CallType ($delta)",
            "message" => "
            C-Name: $db_CommonName
            Loc: $db_FullAddress
            Inc: $db_CallType
            Nature: $db_NatureOfCall
            Cross Rd: $db_NearestCrossStreets
            Beat: $db_PoliceBeat
            Quad: $db_FireQuadrant
            Unit: $db_UnitNumber
            Time: $db_CreateDateTime
            Narr: $db_Narrative_Text",
            "sound" => "bike",
            "html" => "1",
            "url" => "$mapUrl",
            "url_title" => "Driving Directions"
        ),
    ));
    try {
        $result = curl_exec($ch);
        if (curl_error($ch)) {
            throw new Exception(curl_error($ch), curl_errno($ch));
        }
        // Decode JSON data to PHP object
        $obj = json_decode($result, true);
        $status = $obj["status"];
        if ($status <> "1") {
            throw new Exception('Response: ' . $result);
        }
    } catch (Exception $e) {
        // exception is raised it will be handled here
        // $e->getMessage() contains the error message
        $logger->Error("ERROR " . $e->getMessage());
    }
    curl_close($ch);
    $logger->info("Pushover message sent - " . $result);
}
