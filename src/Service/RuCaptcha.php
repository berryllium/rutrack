<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class RuCaptcha
{
    private $urlIn = 'https://rucaptcha.com/in.php';
    private $urlOut = 'https://rucaptcha.com/res.php';
    private $token;
    private $image;
    private $httpClient;
    private $request_id;

    public function __construct(CurlClient $httpClient, $token) {

        $this->httpClient = $httpClient;
        $this->token = $token;
    }

    public function getCodeByImg($image)
    {
        if(!file_exists($image)) throw new \Exception('Image for captcha is not found');
        $this->image = $image;
        $this->request_id = $this->sendImage();
        return $this->checkRequestId();
    }

    private function sendImage()
    {
        $body = [
            'file' => new \CurlFile($this->image),
            'method' => 'post',
            'key' => $this->token,
            'json' => 1,
        ];

        $json = $this->httpClient->sendCurl($this->urlIn, $body);
        $res = json_decode($json, true);
        return $res['request'];
    }

    private function checkRequestId()
    {
        $query = http_build_query([
            'key' => $this->token,
            'id' => $this->request_id,
            'action' => 'get',
            'json' => 1
        ]);
        $time = time();
        while ((time() - $time < 60)) {
            sleep(1);
            $json = $this->httpClient->sendCurl($this->urlOut . '?' .$query);
            $res = json_decode($json, true);
            if($res['status'] == 1) {
                file_put_contents('../var/captcha.txt', $res['request']);
                return $res['request'];
            }
        }
        return false;
    }
}