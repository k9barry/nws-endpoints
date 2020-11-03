<?php

namespace K9Barry;

class initLogger
{
    /**
     * initLogger
     *
     * @return void
     */
    public function initLogger ()
    {
        $logger = new Logger('webhook_logger'); // Create the logger
        $logger->pushHandler(new RotatingFileHandler("./data/Logs/webhook.log", Logger::DEBUG)); // Add sRotatingFileHandler
        $logger->pushProcessor(new IntrospectionProcessor());
        $logger->info('Webhook logger is now ready'); // You can now use your logger
    }
}