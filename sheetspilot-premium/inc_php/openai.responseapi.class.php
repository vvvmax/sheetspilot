<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if (!defined("SHEETSPILOT_INC")) die("restricted access");

class SheetsPilotOpenAIResponseAPI 
{
    var string $apiKey;
    public const ORIGIN = 'https://api.openai.com';
    public const API_VERSION = 'v1';
    public const OPEN_AI_URL = self::ORIGIN . "/" . self::API_VERSION;
    public const OPEN_AI_RESPONSES_URL = self::OPEN_AI_URL . "/responses";


	function __construct( $apiKey ){
        $this->apiKey = $apiKey;
    }

    function runImageCreationQuery( $data ){
        $ch = curl_init( self::OPEN_AI_RESPONSES_URL );
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode($data),
        ]);

        $result = json_decode(curl_exec($ch), true);
        curl_close($ch);

        return $result;
    }
    function checkImageCreationQuery( $queryID ){
        $ch = curl_init( self::OPEN_AI_RESPONSES_URL.'/'.$queryID );
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->apiKey,
            ],
        ]);

        $result = json_decode(curl_exec($ch), true);
        curl_close($ch);
        return $result;
    }
}

