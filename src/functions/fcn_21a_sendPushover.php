<?php

use Psr\Log\LoggerInterface;

/**
 * fcn_21a_sendPushover
 * 
 * Sends incident notifications to Pushover with incident details and Google Maps link.
 * Retrieves incident data from database and formats it into a Pushover notification message.
 *
 * @param PDO $db_conn Database connection (PDO instance)
 * @param string $db_incident Database incident table name
 * @param SimpleXMLElement $xml XML data containing CallId
 * @param string $delta Time delta information for the notification
 * @param LoggerInterface $logger Logger instance for logging operations
 * @return void
 * @throws PDOException When database query fails
 * @throws RuntimeException When Pushover API call fails
 */
function fcn_21a_sendPushover(PDO $db_conn, string $db_incident, SimpleXMLElement $xml, string $delta, LoggerInterface $logger): void
{
    global $pushoverUrl, $pushoverToken, $pushoverUser;
    
    $CallId = (string) $xml->CallId;
    
    try {
        $sql = "SELECT * FROM {$db_incident} WHERE db_CallId = ?";
        $row = $db_conn->prepare($sql);
        $row->execute([$CallId]);

        $pushoverMessage = $row->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($pushoverMessage)) {
            $logger->warning("No incident data found for CallId: {$CallId}");
            return;
        }
        
        extract($pushoverMessage[0]);

        $mapUrl = "https://www.google.com/maps/dir/?api=1&destination={$db_LatitudeY},{$db_LongitudeX}";
        $logger->info("Preparing Pushover notification with Google Maps URL: {$mapUrl}");

        $ch = curl_init();
        if ($ch === false) {
            throw new RuntimeException("Failed to initialize cURL for Pushover notification");
        }

        try {
            curl_setopt_array($ch, [
                CURLOPT_URL => $pushoverUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_POSTFIELDS => [
                    "token" => $pushoverToken,
                    "user" => $pushoverUser,
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
        
    } catch (PDOException $e) {
        $logger->error("Database error in fcn_21a_sendPushover for CallId {$CallId}: " . $e->getMessage());
        throw $e;
    } catch (Exception $e) {
        $logger->error("Error sending Pushover notification for CallId {$CallId}: " . $e->getMessage());
        throw new RuntimeException("Failed to send Pushover notification: " . $e->getMessage(), 0, $e);
    }
}
