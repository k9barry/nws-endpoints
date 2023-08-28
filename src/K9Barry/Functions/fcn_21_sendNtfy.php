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
    $mapUrl = "https://maps.googleapis.com/maps/api/staticmap?center=$db_LatitudeY,$db_LongitudeX&zoom=16&size=400x400&maptype=hybrid&&markers=color:green|label:$urlEncFullAddress%7C$db_LatitudeY,$db_LongitudeX&key=$googleApiKey";
    $logger->info("Open connection to NTFY and set Google Url " . $mapUrl . "");


    file_get_contents("$ntfyUrl", false, stream_context_create([
        'http' => 
        [
            'method' => 'POST',
            'header' => 
                "Content-Type: text/markdown" .
                "X-Priority: default" .
                "X-Tags: fire_engine police_car" .
                "X-Attach: $mapUrl" .
                "X-Markdown: 1" .
                "X-Actions: " .
                "X-Click: " .
                "X-Filename: " .
                "X-Email: " .
                "X-Title: Call: $db_CallNumber $db_CallType ($delta)" .
                "X-Message: C-Name: $db_CommonName
                            Loc: $db_FullAddress
                            Inc: $db_CallType
                            Nature: $db_NatureOfCall
                            Cross Rd: $db_NearestCrossStreets
                            Beat: $db_PoliceBeat
                            Quad: $db_FireQuadrant
                            Unit: $db_UnitNumber
                            Time: $db_CreateDateTime
                            Narr: $db_Narrative_Text" ,
        ]
    ]));
}
