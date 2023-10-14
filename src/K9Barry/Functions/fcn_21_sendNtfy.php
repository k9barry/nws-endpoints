<?php

/**
 * fcn_21_sendNtfy
 *
 * @param  mixed $db_conn
 * @param  mixed $db_incident
 * @param  mixed $xml
 * @param  mixed $delta
 * @param  mixed $logger
 * @param  mixed $topics
 * @param  mixed $resendAll
 * @return void
 */
function fcn_21_sendNtfy($db_conn, $db_incident, $xml, $delta, $logger, $topics, $resendAll)
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
    $mapUrl = "https://maps.googleapis.com/maps/api/staticmap?center=$db_LatitudeY,$db_LongitudeX&zoom=16&size=800x800&scale=2&maptype=hybrid&&markers=color:green|label:$urlEncFullAddress%7C$db_LatitudeY,$db_LongitudeX&key=$googleApiKey";
    $logger->info("Open connection to NTFY and set Google Url " . $mapUrl . "");
    ##Set tag
    if ($db_AgencyType == "Fire") {
        $tags = "fire_engine";
    } else if ($db_AgencyType == "Police") {
        $tags = "police_car";
    } else {
        $tags = "fire_engine,police_car";
    }
    if ($db_AlarmLevel == "1") { //Add alarm level to tag
        $tags = "1st_place_medal," . $tags;
    } else if ($db_AlarmLevel == "2") {
        $tags = "2nd_place_medal," . $tags;
    } else if ($db_AlarmLevel == "3") {
        $tags = "3rd_place_medal," . $tags;
    }

    if ($resendAll == 1) {
        $topics = "" . $db_AgencyType . "|" . $db_Incident_Jurisdiction . "|" . $db_UnitNumber . "";
    }
    $logger->info("########### Ntfy messages will be sent to " . $topics . " #############");
    $topics = explode('|', $topics);
    $topics = array_unique($topics); //Remove any duplicates

    if ($db_CallType <> "New Call") {

        foreach ($topics as $topic) {

            file_get_contents("" . $ntfyUrl . "/" . $topic, false, stream_context_create([
                'http' => [
                    'method' => 'PUT',
                    'header' =>
                        "Content-Type: text/plain \r\n" .
                        #"Authorization: Bearer $ntfyToken \r\n" .
                        "Title: Call: $db_CallNumber $db_CallType ($delta) \r\n" .
                        "Tags: $tags \r\n" .
                        #"Attach: $mapUrl \r\n" .
                        "Click: $mapUrl \r\n" .
                        #"Icon: https://d2gg9evh47fn9z.cloudfront.net/800px_COLOURBOX37302430.jpg \r\n" .
                        "Priority: 4",
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
            $logger->info("========= Ntfy messages sent to topic " . $topic . " =========");
        } //foreach loop
    } // if !str_contains New Call

    fcn_22_removeOldRecords($db_conn, $db_incident, $CallId, $logger);

}