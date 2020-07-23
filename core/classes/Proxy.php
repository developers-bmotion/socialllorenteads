<?php

class Proxy {

    private $client = null;

    private static $instance;

    private function __construct()
    {
        $this->init();
    }

    public static function getInstance()
    {
        if (!self::$instance instanceof self) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function init(){
        global $config;
        $this->client = new ScraperAPI\Client($config['key_scraper']);
    }

    public function getResponse(String $url, Array $headers){
        $config = [
            'headers' => $headers
        ];
        return $this->client->get($url, $config);
    }

    public function __clone()
    {
        exit("don't clone");
    }
}