<?php

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
    global $logger, $pushoverUrl, $pushoverToken, $pushoverUser, $googleApiKey;
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
    $mapUrl = "https://maps.googleapis.com/maps/api/staticmap?center=$db_LatitudeY,$db_LongitudeX&zoom=16&size=400x400&maptype=hybrid&&markers=color:green|label:$urlEncFullAddress%7C$db_LatitudeY,$db_LongitudeX&key=$googleApiKey";
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
    $logger->info("Pushover message sent - " . $result . "");
}
