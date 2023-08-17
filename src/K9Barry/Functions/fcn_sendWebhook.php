<?php

/**
 * fcn_sendWebhook
 *
 * @param  mixed $db_conn
 * @param  mixed $db_incident
 * @param  mixed $xml
 * @param  mixed $delta
 * @param  mixed $logger
 * @return void
 */
function fcn_sendWebhook($db_conn, $db_incident, $xml, $delta, $logger)
{
    global $googleApiKey, $webhookUrl;
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
    $mapUrl = "<a href=\"https://maps.googleapis.com/maps/api/staticmap?center=$db_LatitudeY,$db_LongitudeX&zoom=16&size=800x800&maptype=hybrid&&markers=color:green|label:$urlEncFullAddress%7C$db_LatitudeY,$db_LongitudeX&key=$googleApiKey\">CLICK FOR MAP</a>";
    $logger->info("Open connection to Webhook");

    // create connector instance
    $connector = new \Sebbmyr\Teams\TeamsConnector($webhookUrl);
    // create a custom card
    $card = new \Sebbmyr\Teams\Cards\CustomCard('' . $db_CallNumber . ' ' . $db_CallType, '' . $db_FullAddress . '(' . $delta . ')');
    // add information
    $card->setColor('01BC36')
        #->addFacts($db_CommonName, ['Nature of Call:' => $db_NatureOfCall, 'Narrative:' => $db_Narrative_Text, 'Units:' => $db_UnitNumber, 'Fire Quad:' => $db_FireQuadrant, 'Map Link:' => $mapUrl, 'Cross Street:' => $db_NearestCrossStreets, 'Call DateTime:' => $db_CreateDateTime]);
        ->addFacts($db_CommonName, ['Common Name:' => $db_CommonName])
        ->addFacts($db_NatureOfCall, ['Nature of Call:' => $db_NatureOfCall])
        ->addFacts($db_Narrative_Text, ['Narrative:' => $db_Narrative_Text])
        ->addFacts($db_UnitNumber, ['Units:' => $db_UnitNumber])
        ->addFacts($db_FireQuadrant, ['Fire Quad:' => $db_FireQuadrant])
        ->addFacts($mapUrl, ['Map Link:' => $mapUrl])
        ->addFacts($db_NearestCrossStreets, ['Cross Street:' => $db_NearestCrossStreets])
        ->addFacts($db_CreateDateTime, ['Call DateTime:' => $db_CreateDateTime])
        ->addImage($mapUrl);

    
    
    // send card via connector
    $curlOptTimeout = 30;
    $curlOptConnectTimeout = 10;
    try {
        $connector->send($card, $curlOptTimeout, $curlOptConnectTimeout);
    } catch (Exception $e) {
        // exception is raised and it'll be handled here
        // $e->getMessage() contains the error message
        $logger->Error("ERROR". $e->getMessage() ."");
    }
      $logger->info("Webhook message sent");
}
