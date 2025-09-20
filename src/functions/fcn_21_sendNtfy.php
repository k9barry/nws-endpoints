<?php

/**
 * fcn_21_sendNtfy
 * 
 * Sends incident notifications to ntfy.sh topics with incident details and actions.
 * Creates hierarchical topic structure (Agency/Jurisdiction/Unit) and formats 
 * notification messages with location links and incident information.
 *
 * @param mixed $db_conn Database connection (PDO instance)
 * @param mixed $db_incident Database incident table name
 * @param mixed $xml XML data containing CallId
 * @param mixed $delta Time delta information for the notification
 * @param mixed $logger Logger instance for notification operations
 * @param mixed $topics Topic hierarchy for notification routing
 * @param mixed $resendAll Whether to resend to all topics or just new ones
 * @return void
 */
function fcn_21_sendNtfy(mixed $db_conn, mixed $db_incident, mixed $xml, mixed $delta, mixed $logger, mixed $topics, mixed $resendAll): void
{
    global $ntfyUrl, $ntfyAuthToken, $pushoverSend;
    $CallId = $xml->CallId;
    $sql = "SELECT * FROM $db_incident WHERE db_CallId = ?";
    $row = $db_conn->prepare($sql);
    $row->execute([$CallId]);

    $ntfyMessage = $row->fetchAll(PDO::FETCH_ASSOC);
    extract($ntfyMessage[0]);

    $mapUrl = "https://www.google.com/maps/dir/?api=1&destination=$db_LatitudeY,$db_LongitudeX";

    $logger->info("Open connection to NTFY and set Google Url " . $mapUrl);
    ##Set tag
    if ($db_AgencyType == "Fire") {
        $tags = "fire_engine";
    } elseif ($db_AgencyType == "Police") {
        $tags = "police_car";
    } else {
        $tags = "fire_engine,police_car";
    }
    if ($db_AlarmLevel == "1") { //Add alarm level to tag
        $tags = "1st_place_medal," . $tags;
    } elseif ($db_AlarmLevel == "2") {
        $tags = "2nd_place_medal," . $tags;
    } elseif ($db_AlarmLevel == "3") {
        $tags = "3rd_place_medal," . $tags;
    }

    if ($resendAll == 1) {
        $topics = $db_AgencyType . "|" . $db_Incident_Jurisdiction . "|" . $db_UnitNumber;
    }

    $priority = $db_AlarmLevel + 2;

    $logger->info("########### Ntfy messages will be sent to " . $topics . " #############");
    $topics = explode('|', $topics);
    $topics = array_unique($topics); //Remove any duplicates

    if ($db_CallType != "New Call") {
        foreach ($topics as $topic) {
            file_get_contents("" . $ntfyUrl . "/" . $topic, false, stream_context_create([
                'http' => [
                    'method' => 'PUT',
                    'header' =>
                        "Content-Type: text/plain \r\n" .
                        "Authorization: $ntfyAuthToken \r\n" .
                        "Title: Call: $db_CallNumber $db_CallType ($delta) \r\n" .
                        "Tags: $tags \r\n" .
                        "Attach: $mapUrl \r\n" .
                        "Icon: https://d2gg9evh47fn9z.cloudfront.net/800px_COLOURBOX37302430.jpg \r\n" .
                        "Priority: $priority",
                    'content' => "\r\n
C-Name: $db_CommonName
Loc: $db_FullAddress
Inc: $db_CallType
Nature: $db_NatureOfCall
Cross Rd: $db_NearestCrossStreets
Beat: $db_PoliceBeat
Quad: $db_FireQuadrant
Unit: $db_UnitNumber
Time: $db_CreateDateTime
Narr: $db_Narrative_Text"
                ]
            ]));
            $logger->debug("========= Ntfy messages sent to topic " . $topic . " =========");
        } //foreach loop
    } // if !str_contains New Call
    if ($pushoverSend == "true") {
        fcn_21a_sendPushover($db_conn, $db_incident, $xml, $delta, $logger);
    }

    fcn_22_removeOldRecords($db_conn, $db_incident, $CallId, $logger);
}
