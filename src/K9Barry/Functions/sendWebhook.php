<?php

/**
 * sendWebhook
 *
 * @param  mixed $db_conn
 * @param  mixed $db_incident
 * @param  mixed $xml
 * @param  mixed $delta
 * @return void
 */
function sendWebhook($db_conn, $db_incident, $xml, $delta)
{
    global $logger, $googleApiKey, $webhookUrl;
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
    $mapUrl = "https://maps.googleapis.com/maps/api/staticmap?center=$db_LatitudeY,$db_LongitudeX&zoom=16&size=800x800&
    maptype=hybrid&&markers=color:green|label:$urlEncFullAddress%7C$db_LatitudeY,$db_LongitudeX&key=$googleApiKey";
    $logger->info("Open connection to Webhook");

    // create connector instance
    $connector = new \Sebbmyr\Teams\TeamsConnector($webhookUrl);
    // create a custom card
    $card = new \Sebbmyr\Teams\Cards\CustomCard('' . $db_CallNumber . ' ' . $db_CallType, '' . $db_FullAddress . '(' . $delta . ')');
    // add information
    $card->setColor('01BC36')
        ->addFacts($db_CommonName, ['Nature of Call:' => $db_NatureOfCall, 'Narrative:' => $db_Narrative_Text, 'Units:' => $db_UnitNumber, 'Fire Quad:' => $db_FireQuadrant, 'Cross Street:' => $db_NearestCrossStreets, 'Call DateTime:' => $db_CreateDateTime]);
    // send card via connector
    $curlOptTimeout = 30;
    $curlOptConnectTimeout = 10;
    $connector->send($card, $curlOptTimeout, $curlOptConnectTimeout);

    $logger->info("Webhook message sent");
}
