<?php

use Psr\Log\LoggerInterface;

/**
 * fcn_21_sendNtfy
 * 
 * Sends incident notifications to ntfy.sh topics with incident details and actions.
 * Creates hierarchical topic structure (Agency/Jurisdiction/Unit) and formats 
 * notification messages with location links and incident information.
 *
 * @param PDO $db_conn Database connection (PDO instance)
 * @param string $db_incident Database incident table name
 * @param SimpleXMLElement $xml XML data containing CallId
 * @param string $delta Time delta information for the notification
 * @param LoggerInterface $logger Logger instance for notification operations
 * @param string $topics Topic hierarchy for notification routing
 * @param int $resendAll Whether to resend to all topics (1) or just new ones (0)
 * @return void
 * @throws PDOException When database query fails
 * @throws RuntimeException When notification sending fails
 */
function fcn_21_sendNtfy(PDO $db_conn, string $db_incident, SimpleXMLElement $xml, string $delta, LoggerInterface $logger, string $topics, int $resendAll): void
{
    global $ntfyUrl, $ntfyAuthToken, $pushoverSend;
    
    $CallId = (string) $xml->CallId;
    
    try {
        $sql = "SELECT * FROM {$db_incident} WHERE db_CallId = ?";
        $row = $db_conn->prepare($sql);
        $row->execute([$CallId]);

        $ntfyMessage = $row->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($ntfyMessage)) {
            $logger->warning("No incident data found for CallId: {$CallId}");
            return;
        }
        
        extract($ntfyMessage[0]);

        $mapUrl = "https://www.google.com/maps/dir/?api=1&destination={$db_LatitudeY},{$db_LongitudeX}";
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
                                "Authorization: {$ntfyAuthToken}",
                                "Title: Call: {$db_CallNumber} {$db_CallType} ({$delta})",
                                "Tags: {$tags}",
                                "Attach: {$mapUrl}",
                                "Icon: https://d2gg9evh47fn9z.cloudfront.net/800px_COLOURBOX37302430.jpg",
                                "Priority: {$priority}"
                            ]),
                            'content' => "C-Name: {$db_CommonName}\nLoc: {$db_FullAddress}\nInc: {$db_CallType}\nNature: {$db_NatureOfCall}\nCross Rd: {$db_NearestCrossStreets}\nBeat: {$db_PoliceBeat}\nQuad: {$db_FireQuadrant}\nUnit: {$db_UnitNumber}\nTime: {$db_CreateDateTime}\nNarr: {$db_Narrative_Text}"
                        ]
                    ]);

                    $result = @file_get_contents("{$ntfyUrl}/{$topic}", false, $context);
                    
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

        // Send Pushover notification if enabled
        if ($pushoverSend === "true") {
            fcn_21a_sendPushover($db_conn, $db_incident, $xml, $delta, $logger);
        }

        // Clean up old records
        fcn_22_removeOldRecords($db_conn, $db_incident, $CallId, $logger);
        
    } catch (PDOException $e) {
        $logger->error("Database error in fcn_21_sendNtfy for CallId {$CallId}: " . $e->getMessage());
        throw $e;
    } catch (Exception $e) {
        $logger->error("Error in fcn_21_sendNtfy for CallId {$CallId}: " . $e->getMessage());
        throw new RuntimeException("Failed to send NTFY notification: " . $e->getMessage(), 0, $e);
    }
}
