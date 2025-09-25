<?php

use Psr\Log\LoggerInterface;

/**
 * fcn_21_sendMessage
 * 
 * Sends incident notifications to both ntfy.sh and Pushover services with incident details.
 * Creates hierarchical topic structure for ntfy (Agency/Jurisdiction/Unit) and formats 
 * notification messages with location links and incident information for both services.
 *
 * @param PDO $db_conn Database connection (PDO instance)
 * @param string $db_incident Database incident table name
 * @param SimpleXMLElement $xml XML data containing CallId
 * @param string $delta Time delta information for the notification
 * @param LoggerInterface $logger Logger instance for notification operations
 * @param string $topics Topic hierarchy for notification routing (ntfy only)
 * @param int $resendAll Whether to resend to all topics (1) or just new ones (0)
 * @param array $config Configuration array containing notification settings
 * @return void
 * @throws PDOException When database query fails
 * @throws RuntimeException When notification sending fails
 */
function fcn_21_sendMessage(PDO $db_conn, string $db_incident, SimpleXMLElement $xml, string $delta, LoggerInterface $logger, string $topics, int $resendAll, array $config): void
{
    $CallId = (string) $xml->CallId;
    
    try {
        $sql = "SELECT * FROM {$db_incident} WHERE db_CallId = ?";
        $row = $db_conn->prepare($sql);
        $row->execute([$CallId]);

        $incidentData = $row->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($incidentData)) {
            $logger->warning("No incident data found for CallId: {$CallId}");
            return;
        }
        
        extract($incidentData[0]);

        $mapUrl = "https://www.google.com/maps/dir/?api=1&destination={$db_LatitudeY},{$db_LongitudeX}";
        
        // Send NTFY notifications if enabled
        if ($config['ntfy']['send'] === true || $config['ntfy']['send'] === "true") {
            sendNtfyNotification($incidentData[0], $mapUrl, $delta, $topics, $resendAll, $logger, $config);
        }

        // Send Pushover notification if enabled
        if ($config['pushover']['send'] === true || $config['pushover']['send'] === "true") {
            sendPushoverNotification($incidentData[0], $mapUrl, $delta, $logger, $config);
        }

        // Clean up old records
        fcn_22_removeOldRecords($db_conn, $db_incident, $CallId, $logger);
        
    } catch (PDOException $e) {
        $logger->error("Database error in fcn_21_sendMessage for CallId {$CallId}: " . $e->getMessage());
        throw $e;
    } catch (Exception $e) {
        $logger->error("Error in fcn_21_sendMessage for CallId {$CallId}: " . $e->getMessage());
        throw new RuntimeException("Failed to send notifications: " . $e->getMessage(), 0, $e);
    }
}

/**
 * Send NTFY notification
 * 
 * @param array $incidentData Extracted incident data from database
 * @param string $mapUrl Google Maps URL for incident location
 * @param string $delta Time delta information
 * @param string $topics Topic hierarchy for notification routing
 * @param int $resendAll Whether to resend to all topics
 * @param LoggerInterface $logger Logger instance
 * @param array $config Configuration array containing ntfy settings
 * @return void
 * @throws RuntimeException When NTFY notification sending fails
 */
function sendNtfyNotification(array $incidentData, string $mapUrl, string $delta, string $topics, int $resendAll, LoggerInterface $logger, array $config): void
{
    extract($incidentData);
    
    $logger->info("Preparing NTFY notification with Google Maps URL: {$mapUrl}");

    // Set tags based on agency type
    $tags = match ($db_AgencyType) {
        "Fire" => "fire_engine",
        "Police" => "police_car",
        default => "fire_engine,police_car"
    };

    // Add alarm level to tags
    if (isset($db_AlarmLevel)) {
        $alarmTag = match ((string) $db_AlarmLevel) {
            "1" => "1st_place_medal",
            "2" => "2nd_place_medal", 
            "3" => "3rd_place_medal",
            default => null
        };
        
        if ($alarmTag) {
            $tags = "{$alarmTag},{$tags}";
        }
    }

    if ($resendAll === 1) {
        $topics = "{$db_AgencyType}|{$db_Incident_Jurisdiction}|{$db_UnitNumber}";
    }

    $priority = ((int) ($db_AlarmLevel ?? 1)) + 2;
    $priority = max(1, min(5, $priority)); // Ensure priority is between 1-5
    
    $logger->info(str_repeat("=", 53));
    $logger->info("NTFY {$db_CallType} at {$db_FullAddress} will be sent to topics: {$topics}");
    
    $topicArray = array_unique(array_filter(explode('|', $topics)));

    if ($db_CallType !== "New Call") {
        foreach ($topicArray as $topic) {
            $topic = trim($topic);
            if (empty($topic)) {
                continue;
            }
            
            try {
                $context = stream_context_create([
                    'http' => [
                        'method' => 'PUT',
                        'timeout' => 10,
                        'header' => implode("\r\n", [
                            "Content-Type: text/plain",
                            "Authorization: {$config['ntfy']['authToken']}",
                            "Title: Call: {$db_CallNumber} {$db_CallType} ({$delta})",
                            "Tags: {$tags}",
                            "Attach: {$mapUrl}",
                            "Icon: https://d2gg9evh47fn9z.cloudfront.net/800px_COLOURBOX37302430.jpg",
                            "Priority: {$priority}"
                        ]),
                        'content' => "C-Name: {$db_CommonName}\nLoc: {$db_FullAddress}\nInc: {$db_CallType}\nNature: {$db_NatureOfCall}\nCross Rd: {$db_NearestCrossStreets}\nBeat: {$db_PoliceBeat}\nQuad: {$db_FireQuadrant}\nUnit: {$db_UnitNumber}\nTime: {$db_CreateDateTime}\nNarr: {$db_Narrative_Text}"
                    ]
                ]);

                $result = @file_get_contents("{$config['ntfy']['url']}/{$topic}", false, $context);
                
                if ($result === false) {
                    $error = error_get_last();
                    $errorMessage = $error ? $error['message'] : 'Unknown error';
                    throw new RuntimeException("Failed to send notification to topic {$topic}: {$errorMessage}");
                }
                
                $logger->info("NTFY message sent successfully to topic: {$topic}");
                
            } catch (Exception $e) {
                $logger->error("Error sending NTFY message to topic {$topic}: " . $e->getMessage());
                // Continue with other topics
            }
        }
    }
}

/**
 * Send Pushover notification
 * 
 * @param array $incidentData Extracted incident data from database
 * @param string $mapUrl Google Maps URL for incident location
 * @param string $delta Time delta information
 * @param LoggerInterface $logger Logger instance
 * @param array $config Configuration array containing pushover settings
 * @return void
 * @throws RuntimeException When Pushover notification sending fails
 */
function sendPushoverNotification(array $incidentData, string $mapUrl, string $delta, LoggerInterface $logger, array $config): void
{
    extract($incidentData);
    
    $logger->info("Preparing Pushover notification with Google Maps URL: {$mapUrl}");

    $ch = curl_init();
    if ($ch === false) {
        throw new RuntimeException("Failed to initialize cURL for Pushover notification");
    }

    try {
        curl_setopt_array($ch, [
            CURLOPT_URL => $config['pushover']['url'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_POSTFIELDS => [
                "token" => $config['pushover']['token'],
                "user" => $config['pushover']['user'],
                "title" => "MCCD Call: {$db_CallNumber} {$db_CallType} ({$delta})",
                "message" => "C-Name: {$db_CommonName}\nLoc: {$db_FullAddress}\nInc: {$db_CallType}\nNature: {$db_NatureOfCall}\nCross Rd: {$db_NearestCrossStreets}\nBeat: {$db_PoliceBeat}\nQuad: {$db_FireQuadrant}\nUnit: {$db_UnitNumber}\nTime: {$db_CreateDateTime}\nNarr: {$db_Narrative_Text}",
                "sound" => "bike",
                "html" => "1",
                "url" => $mapUrl,
                "url_title" => "Driving Directions"
            ]
        ]);

        $result = curl_exec($ch);
        
        if ($result === false) {
            $curlError = curl_error($ch);
            $curlErrno = curl_errno($ch);
            throw new RuntimeException("cURL error ({$curlErrno}): {$curlError}");
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($httpCode !== 200) {
            throw new RuntimeException("HTTP error: {$httpCode}. Response: {$result}");
        }

        // Decode and validate JSON response
        $responseData = json_decode($result, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException("Invalid JSON response from Pushover: " . json_last_error_msg());
        }

        if (!isset($responseData["status"]) || $responseData["status"] !== 1) {
            $errorMessage = $responseData["errors"][0] ?? "Unknown error";
            throw new RuntimeException("Pushover API error: {$errorMessage}");
        }

        $logger->info("Pushover message sent successfully - Response: {$result}");

    } finally {
        curl_close($ch);
    }
}