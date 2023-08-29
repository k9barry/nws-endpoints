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
    ##Set tag
    $tags = "fire_engine,police_car";
    ##Add alarm level to tag
    if ($db_AlarmLevel = "1") {
        $tags = "1st_place_medal,". $tags;
    } else if ($db_AlarmLevel = "2") {
        $tags = "2nd_place_medal,". $tags;
    } else if ($db_AlarmLevel = "3") {
        $tags = "3rd_place_medal,". $tags;
    }

    file_get_contents("$ntfyUrl", false, stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json",
            'content' => json_encode([
                "topic": "test",
                "title": "Call: $db_CallNumber $db_CallType ($delta)",
                "tags": ["$tags"],
                "priority": "4",
                "attach": "$mapUrl",
                "filename": "diskspace.jpg",
                "click": "https://ntfy.jafcp.com/",
                "actions": [["action": "view", "label": "Map", "url": "$mapUrl"]],
                "message": [
                    "C-Name: $db_CommonName",
                    "C-Name: $db_CommonName",
                    "Loc: $db_FullAddress",
                    "Inc: $db_CallType",
                    "Nature: $db_NatureOfCall",
                    "Cross Rd: $db_NearestCrossStreets",
                    "Beat: $db_PoliceBeat",
                    "Quad: $db_FireQuadrant",
                    "Unit: $db_UnitNumber",
                    "Time: $db_CreateDateTime",
                    "Narr: $db_Narrative_Text"
                ]
            ])
        ]
    ]));
}
