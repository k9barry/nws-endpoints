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

    $ch = curl_init();
    $options = aray(CURLOPT_URL => "$ntfyUrl",
                    CURLOPT_RETURNTRANSFER => true,)
                    CURLOPT_POSTFIELDS => array(
                            "X-Priority" => "default",  # urgent|high|default|low|min   https://docs.ntfy.sh/publish/#message-priority
                            "X-Tags" => "fire_engine | police_car",  # https://docs.ntfy.sh/publish/#tags-emojis
                            "X-Title" => "Call: $db_CallNumber $db_CallType ($delta)",  # https://docs.ntfy.sh/publish/#message-title
                            "X-Message" => "
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
                            "X-Actions" => "",  # https://docs.ntfy.sh/publish/#click-action
                            "X-Click" => "",  # https://docs.ntfy.sh/publish/#click-action
                            "X-Attach" => "$mapUrl",  # https://docs.ntfy.sh/publish/#attachments
                            "X-Filename" => "",  # https://docs.ntfy.sh/publish/#attachments
                            "X-Email" => "",  # https://docs.ntfy.sh/publish/#e-mail-notifications
                            "X-Markdown" => "1"  #Markdown is supported 1|0 https://www.markdownguide.org/basic-syntax/
                        ),
                    ));
    curl_setopt_array($ch, $options);

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
