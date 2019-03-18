<?php
/**
 * Created by PhpStorm.
 * User: i.sutugins
 * Date: 14.4.9
 * Time: 16:42
 */

class IntrumCommunicator
{
    private $server;

    /**
     * @param mixed $server
     */
    public function setServer($server)
    {
        $this->server = $server;
    }

    /**
     * @return mixed
     */
    public function getServer()
    {
        return $this->server;
    }

    public function sendRequest($xmlRequest) {
        $response = "";
        if ($this->server == 'test') {
            $sslsock = fsockopen("ssl://secure.intrum.ch", 443, $errno, $errstr, 30);
        } else {
            $sslsock = fsockopen("ssl://secure.intrum.ch", 443, $errno, $errstr, 30);
        }

        if(is_resource($sslsock)) {

            $request_data	= urlencode("REQUEST")."=".urlencode($xmlRequest);
            $request_length	= strlen($request_data);

            if ($this->server == 'test') {
                fputs($sslsock, "POST /services/creditCheckDACH_01_41_TEST/response.cfm HTTP/1.0\r\n");
            } else {
                fputs($sslsock, "POST /services/creditCheckDACH_01_41/response.cfm HTTP/1.0\r\n");
            }

            fputs($sslsock, "Host: intrum.com\r\n");
            fputs($sslsock, "Content-type: application/x-www-form-urlencoded\r\n");
            fputs($sslsock, "Content-Length: ".$request_length."\r\n");
            fputs($sslsock, "Connection: close\r\n\r\n");
            fputs($sslsock, $request_data);

            while(!feof($sslsock)) {
                $response .= @fgets($sslsock, 128);
            }

            fclose($sslsock);

            $response = substr($response, strpos($response,'<?xml')-1);
            $response = substr($response, 1,strpos($response,'Response>')+8);

            if (empty($response)) {
                throw new Exception("Error connect to Intrum");
            }

        } else {

            throw new Exception("Error connect to Intrum");

        }
        return $response;
    }
};