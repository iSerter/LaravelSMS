<?php

namespace SimpleSoftwareIO\SMS\Drivers;

use SimpleSoftwareIO\SMS\OutgoingMessage;


/**
 * Class VerimorSMS
 * @package SimpleSoftwareIO\SMS\Drivers
 */
class VerimorSMS implements DriverInterface
{

    /**
     * @var
     */
    private $username;
    /**
     * @var
     */
    private $password;


    public function __construct($username, $password)
    {
        $this->username = $username;
        $this->password = $password;
    }

    public function send(OutgoingMessage $message)
    {
        $from = $message->getFrom();
        $composeMessage = $message->composeMessage();

        //Convert to callfire format.
        $response = $this->sendSMSRequest($composeMessage, $message->getTo(), $from);
    }

    public function checkMessages(array $options = [])
    {
        throw new \Exception('Checking messages is not available in Verimor SMS');
    }

    public function getMessage($messageId)
    {
        throw new \Exception('Reading messages is not available in Verimor SMS');
    }


    public function receive($raw)
    {
        throw new \Exception('Receiving messages is not available in Verimor SMS');
    }

    private function sendSMSRequest($message, array $phoneNumbers, $header = null)
    {
        array_walk($phoneNumbers, function (&$item) {
            $item = preg_replace('/\D/', '', $item);
        });

        $payload = array(
            "username" => $this->username, // https://oim.verimor.com.tr/sms_settings/edit adresinden öğrenebilirsiniz.
            "password" => $this->password, // https://oim.verimor.com.tr/sms_settings/edit adresinden belirlemeniz gerekir.
            "source_addr" => $header, // Gönderici başlığı, https://oim.verimor.com.tr/headers adresinde onaylanmış olmalı, değilse 400 hatası alırsınız.
            //    "valid_for" => "48:00",
            //    "send_at" => "2015-02-20 16:06:00",
            //    "datacoding" => "0",
            //"custom_id" => "1424441160.9331344",
            "messages" => array(
                array(
                    "msg" => $message,
                    "dest" => implode(',', $phoneNumbers)
                )
            )
        );
        $ch = curl_init('http://sms.verimor.com.tr/v2/send.json');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => array('Content-Type: application/json'),
            CURLOPT_POSTFIELDS => json_encode($payload),
        ]);
        $httpResponse = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($httpCode != 200) {
            throw new \Exception("Error while sending sms request to Verimor", $httpCode);
        }
        return $httpResponse;
    }
}