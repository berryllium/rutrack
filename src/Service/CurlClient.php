<?php

namespace App\Service;

class CurlClient
{
    private $app_proxy_ip;
    private $app_proxy_auth;
    private $app_proxy_port;

    public function __construct($app_proxy_ip, $app_proxy_auth, $app_proxy_port)
    {
        $this->app_proxy_ip = $app_proxy_ip;
        $this->app_proxy_auth = $app_proxy_auth;
        $this->app_proxy_port = $app_proxy_port;
    }

    public function sendCurl($url = '', $post = [], $file = '', $getHeader = false, $referer = '') {
        $fp = false;
        $header = [
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.163 Safari/537.36',
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
            'Sec-Fetch-Dest: document',
            'Upgrade-Insecure-Requests: 1',
            'Keep-Alive: 115',
            'Connection: keep-alive'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 600);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 600);
        curl_setopt($ch,CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_VERBOSE, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_COOKIESESSION, true );
        curl_setopt($ch,CURLOPT_COOKIEFILE, $_SERVER['DOCUMENT_ROOT']. '../var/curl_cookies.txt');
        curl_setopt($ch,CURLOPT_COOKIEJAR,  $_SERVER['DOCUMENT_ROOT']. '../var/curl_cookies.txt');
        curl_setopt($ch, CURLOPT_URL, $url);

        curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 1);
        curl_setopt($ch, CURLOPT_PROXY, 'http://'.$this->app_proxy_ip);
        curl_setopt($ch, CURLOPT_PROXYUSERPWD, $this->app_proxy_auth);
        curl_setopt($ch, CURLOPT_PROXYPORT, $this->app_proxy_port);


        if ($getHeader)
        {
            curl_setopt($ch, CURLOPT_HEADER, true);
        }
        else
        {
            curl_setopt($ch, CURLOPT_HEADER, false);
        }
        if ($post)
        {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        }
        if ($referer)
        {
            curl_setopt($ch, CURLOPT_REFERER, $referer);
        }
        if ($file)
        {
            $fp = fopen($file, 'w');
            curl_setopt($ch, CURLOPT_FILE, $fp);
        }
        $result = curl_exec($ch);
        curl_close($ch);
        if ($fp)
        {
            fclose($fp);
        }
        return $result;
    }
}