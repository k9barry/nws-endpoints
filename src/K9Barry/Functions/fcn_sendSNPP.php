<?php

/**
 * fcn_sendSNPP
 *
 * @param  mixed $db_conn
 * @param  mixed $db_incident
 * @param  mixed $xml
 * @param  mixed $logger
 * @return void
 */
function fcn_sendSNPP($db_conn, $db_incident, $xml, $logger)
{
    try {

        global $snppUrl, $snppPort, $snppPage;
        $CallId = $xml->CallId;
        $sql = "SELECT * FROM $db_incident WHERE db_CallId = '$CallId'";
        $row = $db_conn->prepare($sql);
        $row->execute();
        $snppMessage = $row->fetchAll(PDO::FETCH_ASSOC);
        $snpp = fsockopen($snppUrl, $snppPort, $errno, $errstr);
        if (!$snpp) {
            throw new \Exception('Response: ' . $result);
            $logger->Error("Fsockopen error - " . $errstr($errno) . "");
        } else {
            $logger->info("Open connection to Active911 - " . fgets($snpp) . "");
            fwrite($snpp, "PAGE $snppPage\r\n");
            $logger->info("Execute PAGEr number to " . $snppPage . " - " . fgets($snpp) . "");
            fwrite($snpp, "DATA\r\n");
            $logger->info("Set DATA protocol - " . fgets($snpp) . "");
            $out = $sep = '';
            foreach ($snppMessage[0] as $key => $value) {
                $out .= $sep . $key . ":" . $value . "\n";
                $sep = '';
            }
            fwrite($snpp, "$out\r\n");
            fwrite($snpp, ".\r\n");
            $logger->info("\n" . $out . "");
            $logger->info("" . fgets($snpp) . "");
            fwrite($snpp, "SEND\r\n");
            $logger->info("Execute SEND - " . fgets($snpp) . "");
            fwrite($snpp, "QUIT\r\n");
            $logger->info("Execute QUIT - " . fgets($snpp) . "");
            fclose($snpp);
        }
    } catch (Exception $e) {
        // exception is raised and it'll be handled here
        // $e->getMessage() contains the error message
        $logger->Error("ERROR ". $e->getMessage() ."");
    }
}