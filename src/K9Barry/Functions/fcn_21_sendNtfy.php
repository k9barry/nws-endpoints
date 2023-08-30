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
    if ($db_AgencyType == "Fire") {
        $tags = "fire_engine";
    } else if ($db_AgencyType == "Police") {
        $tags = "police_car";
    } else {
        $tags = "fire_engine,police_car";
    }
    if ($db_AlarmLevel == "1") {  #Add alarm level to tag
        $tags = "1st_place_medal,". $tags;
    } else if ($db_AlarmLevel == "2") {
        $tags = "2nd_place_medal,". $tags;
    } else if ($db_AlarmLevel == "3") {
        $tags = "3rd_place_medal,". $tags;
    }


/*####################################################
#Get str value from the database for Agency and convert to array
#Get str value from the database for Jurisdiction and convert to array
#Get str value from the database for Unit and convert to array
#Merge these into one array
#Get str value from the xmlfile for Agency and convert to array
#Get str value from the xmlfile for Jurisdiction and convert to array
#Get str value from the xmlfile for Unit and convert to array
#Merge these into one array
#Compare the two arrays and get different/unique values
#Send the difference to the For Each $topics loop

$str_xml_AgencyType = $sep = '';
$nrOfRows = $xml->AgencyContexts->AgencyContext->count();
$n = 0;
for ($n = 0; $n < $nrOfRows; $n++) {
    $value = $xml->AgencyContexts->AgencyContext[$n]->AgencyType;
    $str_xml_AgencyType .= $sep . $value;
    $sep = '|';
}
$arr_xml_AgencyType = array_filter(explode('|', $str_xml_AgencyType));

// $Incidents_Incident_Jurisdiction = $xml->Incidents->Incident->Jurisdiction;
$str_xml_Jurisdiction = $sep = '';
$nrOfRows = $xml->Incidents->Incident->count();
$n = 0;
for ($n = 0; $n < $nrOfRows; $n++) {
    $value = $xml->Incidents->Incident[$n]->Jurisdiction;
    $str_xml_Jurisdiction .= $sep . $value;
    $sep = '|';
}
$arr_xml_Jurisdiction = array_filter(explode('|', $str_xml_Jurisdiction));

// $AssignedUnits_Unit_UnitNumber = $xml->AssignedUnits->Unit->UnitNumber;
$str_xml_UnitNumber = $sep = '';
$nrOfRows = $xml->AssignedUnits->Unit->count();
$n = 0;
for ($n = 0; $n < $nrOfRows; $n++) {
    $value = $xml->AssignedUnits->Unit[$n]->UnitNumber;
    $str_xml_UnitNumber .= $sep . $value;
    $sep = '|';
}
$arr_xml_UnitNumber = array_filter(explode('|', $str_xml_UnitNumber));

$topics_arrXml = array_merge($arr_xml_AgencyType, $arr_xml_Jurisdiction, $arr_xml_UnitNumber);

$topics_arrDb_Agency = explode("|", $db_AgencyType);
$topics_arrDb_Jurisdiction = explode("|", $db_Incident_Jurisdiction);
$topics_arrDb_Unit = explode("|", $db_UnitNumber);

$topics_arrDb = array_merge($topics_arrDb_Agency, $topics_arrDb_Jurisdiction, $topics_arrDb_Unit);


$topics = array_diff($topics_arrXml, $topics_arrDb);
var_dump($topics);
#$logger->info("########### Ntfy messages will be sent to " . $topics . " #############");
/*
    // $AssignedUnits_Unit_UnitNumber = $xml->AssignedUnits->Unit->UnitNumber;
    $arr_db_UnitNumber = array_filter(explode('|', $db_UnitNumber));
    $str_xml_UnitNumber = $sep = '';
    $nrOfRows = $xml->AssignedUnits->Unit->count();
    $n = 0;
    for ($n = 0; $n < $nrOfRows; $n++) {
        $value = $xml->AssignedUnits->Unit[$n]->UnitNumber;
        $str_xml_UnitNumber .= $sep . $value;
        $sep = '|';
    }
    $arr_xml_UnitNumber = array_filter(explode('|', $str_xml_UnitNumber));

    if ($arr_db_UnitNumber != $arr_xml_UnitNumber) {
        $logger->info("Resend because unit number was added");
        #$send = 1;
        $db_UnitNumber = array_diff($arr_db_UnitNumber, $arr_xml_UnitNumber);
        $db_UnitNumber = implode("|", $db_UnitNumber);
    }
####################################################*/

    Gather all topics to send to
    $topics = "" . $db_AgencyType . "|" . $db_Incident_Jurisdiction . "|" . $db_UnitNumber . "";
    $logger->info("########### Ntfy messages will be sent to " . $topics . " #############");
    $topics = explode('|',$topics);
    
foreach ($topics as $topic) {
    file_get_contents("".$ntfyUrl."/".$topic, false, stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' =>
                "Content-Type: text/plain \r\n" .
                "Authorization: Bearer $ntfyToken \r\n" .
                "Title: Call: $db_CallNumber $db_CallType ($delta) \r\n" .
                "Tags: $tags \r\n" .
                "Attach: $mapUrl \r\n" .
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
}
}