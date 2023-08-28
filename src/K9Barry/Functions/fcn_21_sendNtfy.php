<?php

/**
 * fcn_21_sendNtfy
 *
 * @param  mixed $db_conn
 * @param  mixed $db_incident
 * @param  mixed $xml
 * @param  mixed $delta
 * @param  mixed $logger
 * @return void
 */
function fcn_21_sendNtfy($db_conn, $db_incident, $xml, $delta, $logger)
{
    global $ntfyUrl, $ntfyToken, $ntfyUser, $googleApiKey;
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
    $urlEncFullAddress = urlencode($db_FullAddress);
    #$mapUrl = "<a href=\"https://maps.googleapis.com/maps/api/staticmap?center=$db_LatitudeY,$db_LongitudeX&zoom=16&size=800x800&maptype=hybrid&&markers=color:green|label:$urlEncFullAddress%7C$db_LatitudeY,$db_LongitudeX&key=$googleApiKey\">CLICK FOR MAP</a>";
    $mapUrl = "https://maps.googleapis.com/maps/api/staticmap?center=$db_LatitudeY,$db_LongitudeX&zoom=16&size=400x400&maptype=hybrid&&markers=color:green|label:$urlEncFullAddress%7C$db_LatitudeY,$db_LongitudeX&key=$googleApiKey";
    $logger->info("Open connection to NTFY and set Google Url " . $mapUrl . "");
    curl_setopt_array($ch = curl_init(), array(
        CURLOPT_URL => "$ntfyUrl",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => array(
            "token" => "$ntfyToken",
            "user" => "$ntfyUser",
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
            "attachment" => curl_file_create("$mapUrl", "image/jpeg")
        ),
    ));
    try {
        $result = curl_exec($ch);
        if (curl_error($ch)) {
            throw new \Exception(curl_error($ch), curl_errno($ch));
        }
        // Decode JSON data to PHP object
        $obj = json_decode($result, true);
        $status = $obj["status"];
        if ($status <> "1") {
            throw new \Exception('Response: ' . $result);
        }
    } catch (Exception $e) {
        // exception is raised and it'll be handled here
        // $e->getMessage() contains the error message
        $logger->Error("ERROR " . $e->getMessage() . "");
    }
    curl_close($ch);
    $logger->info("Ntfy message sent - " . $result . "");
}
