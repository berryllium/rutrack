<?php

namespace App\Service\Torrent;

use App\Service\RuCaptcha;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class RutrackerClient implements TorrentClientInterface
{


    private $login;
    private $pass;
    private $loginUrl;
    private $searchUrl;
    private $forumUrl;
    private $httpClient;
    private $proxy;
    private $captcha;
    private $captchaPath = '../var/rutracker_captcha.jpg';
    private $transmission;
    private $sort_options = [
        "asc" => 1,
        "desc" => 2
    ];
    private $order_options = [
        "date" => 1,
        "name" => 2,
        "downloads" => 4,
        "shows" => 6,
        "seeders" => 10,
        "leechers" => 11,
        "size" => 7,
        "last_post" => 8,
        "speed_up" => 12,
        "speed_down" => 13,
        "message_count" => 5,
        "last_seed" => 9
    ];



    public function __construct(
        $login,
        $pass,
        $loginUrl,
        $searchUrl,
        $forumUrl,
        $proxy,
        HttpClientInterface $httpClient,
        RuCaptcha $captcha,
        TransmissionClient $transmission
    ) {
        $this->login = $login;
        $this->pass = $pass;
        $this->loginUrl = $loginUrl;
        $this->searchUrl = $searchUrl;
        $this->httpClient = $httpClient;
        $this->proxy = $proxy;
        $this->captcha = $captcha;
        $this->forumUrl = $forumUrl;
        $this->transmission = $transmission;
    }

    /**
     * @inheritDoc
     */
    public function auth(): bool
    {
        $result = $this->httpClient->request('GET', $this->loginUrl, [
            'proxy' => $this->proxy,
            'verify_peer' => false,
            'verify_host' => false,
        ]);
        $fields = [];
        $crawler = new Crawler($result->getContent());
        $inputs = $crawler->filter('#login-form-full input');
        $inputs->each(function ($node) use (&$fields){
            $name = $node->attr('name');
            $value = $node->attr('value');
            if($name) {
                if($name == 'login_username') $fields[$name] = $this->login;
                elseif($name == 'login_password') $fields[$name] = $this->pass;
                elseif(strpos($name, 'cap_code') !== false) $fields[$name] = 'cap_code';
                else $fields[$name] = $value;
            }
        });

        $capCodeFieldName = array_search('cap_code', $fields);
        if($capCodeFieldName) {
            $img_captcha = $crawler->filter('#login-form-full img')->attr('src');
            file_put_contents($this->captchaPath, file_get_contents($img_captcha));
            $fields[$capCodeFieldName] = $this->captcha->getCodeByImg($this->captchaPath);
        }

        $result = $this->httpClient->request('POST', $this->loginUrl, [
            'proxy' => $this->proxy,
            'verify_peer' => false,
            'verify_host' => false,
            'body' => $fields,
            'headers' => [
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
                'Accept-Encoding' => 'gzip, deflate, br',
                'Accept-Language' => 'ru,en;q=0.9,uk;q=0.8',
                'Cache-Control' => 'max-age=0',
                'Connection' => 'keep-alive',
                'Host' => 'rutracker.org',
                'sec-ch-ua' => '" Not A;Brand";v="99", "Chromium";v="96", "Google Chrome";v="96"',
                'sec-ch-ua-mobile' => '?0',
                'sec-ch-ua-platform' => '"Windows"',
                'Sec-Fetch-Dest' => 'document',
                'Sec-Fetch-Mode' => 'navigate',
                'Sec-Fetch-Site' => 'none',
                'Sec-Fetch-User' => '?1',
                'Upgrade-Insecure-Requests' => '1',
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/96.0.4664.110 Safari/537.36',
            ]
        ]);

        return true;
    }

    /**
     * @inheritDoc
     */
    public function search(string $q): array
    {
        $this->auth();
        $body = [
            'f' => [-1],
            'o' => $this->order_options['seeders'],
            's' => $this->sort_options['desc'],
            'pn' => '',
        ];

        // TODO cookies захардкожены, не получается отловить в хедере, можно подсмотреть в браузере при авторизации

        $result = $this->httpClient->request('POST', $this->searchUrl . '?nm='. $q, [
            'proxy' => $this->proxy,
            'verify_peer' => false,
            'verify_host' => false,
            'body' => $body,
            'headers' => [
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
                'Accept-Language' => 'ru,en',
                'Connection' => 'keep-alive',
                'Cookie' => 'bb_guid=VCy5uGiwOian; bb_ssl=1; bb_session=0-20363984-h2oefpeC9ZvZceVnqhYs; _ym_uid=1646934595566740400; _ym_d=1646934595; cf_clearance=5gIzbMM2LMsG6tozrXCYU2vt3vZEob8N.Mv6u672ALU-1646934659-0-150; _ym_isad=2',
                'Host' => 'rutracker.org',
                'sec-ch-ua' => '" Not A;Brand";v="99", "Chromium";v="96", "Google Chrome";v="96"',
                'sec-ch-ua-mobile' => '?0',
                'sec-ch-ua-platform' => '"Windows"',
                'Sec-Fetch-Dest' => 'document',
                'Sec-Fetch-Mode' => 'navigate',
                'Sec-Fetch-Site' => 'none',
                'Sec-Fetch-User' => '?1',
                'Upgrade-Insecure-Requests' => '1',
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/96.0.4664.110 Safari/537.36',
            ]
        ]);

        $crawler = new Crawler($result->getContent());

        $table = $crawler->filter('table.forumline tr')->each(function ($tr) {
            return $tr->filter('td')->each(function ($td, $k) {
                $text = trim($td->text());
                if($k == 2) {
                    return ['code' => 'section', 'value' => $text];
                } elseif($k == 3) {
                    return ['code' => 'name', 'link' => $td->filter('a')->attr('href'), 'value' => $text];
                } elseif($k == 5) {
                    return ['code' => 'size',  'value' => $text];
                } elseif($k == 6) {
                    return ['code' => 'seed',  'value' => $text];
                } elseif($k == 7) {
                    return ['code' => 'leech',  'value' => $text];
                }
                return null;
            });
        });

        $result = [];
        foreach ($table as $k => $tr) {
            foreach ($tr as $td) {
                if($td) {
                    $code = $td['code'];
                    unset($td['code']);
                    if($code == 'name') {
                        $result[$k]['link'] = $td['link'];
                        $result[$k]['fullname'] = $td['value'];
                        $arr = explode(' ', $td['value']);
                        $name = implode(' ', array_slice($arr, 0, 7));
                        $result[$k]['name'] = $name;
                    } else {
                        $result[$k][$code] = $td['value'];
                    }
                }
            }
        }

        return array_values($result);
    }

    /**
     * @inheritDoc
     */
    public function getMagnet(string $url): string
    {
        $result = $this->httpClient->request('GET', $this->forumUrl . $url, [
            'proxy' => $this->proxy,
            'verify_peer' => false,
            'verify_host' => false,
        ]);
        $crawler = new Crawler($result->getContent());
        $magnet_link = $crawler->filter('a.magnet-link')->attr('href');
        return $this->transmission->add($magnet_link)->getId();
    }
}