<?php

class Xhgui_Saver_Upload implements Xhgui_Saver_Interface
{

    protected $uri;

    public function __construct($uri)
    {
        $this->uri = $uri;
    }

    public function save($data)
    {
        $json = json_encode($data);

        $ch = curl_init($this->uri);

        $headers = array(
            'Accept: application/json',         // Prefer to receive JSON back
            'Content-Type: application/json'    // The sent data is JSON
        );

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        curl_exec($ch);

        curl_close($ch);
    }
}
