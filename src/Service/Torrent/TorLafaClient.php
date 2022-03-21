<?php

namespace App\Service\Torrent;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class TorLafaClient implements TorrentClientInterface
{

    private string $forumUrl = 'https://tor.lafa.site';
    private string $searchUrl = 'https://tor.lafa.site/ajax.php';

    private HttpClientInterface $httpClient;
    private TransmissionClient $transmission;
    /**
     * @var false|mixed
     */
    private $torrent;

    public function __construct(HttpClientInterface $httpClient, TransmissionClient $transmission)
    {

        $this->httpClient = $httpClient;
        $this->transmission = $transmission;
    }

    public function auth(): bool
    {
        return true;
    }

    public function search(string $q): ?array
    {
        $body = [
            'rnd' => (1 / rand(1, 1000)) * 100,
            'action' => 'quicksearch',
            'keyword' => $q,
        ];

        $result = $this->httpClient->request('GET', $this->searchUrl . '?' . http_build_query($body), [
            'verify_peer' => false,
            'verify_host' => false,
            'headers' => [
                'Content-Type' => 'text/html; charset=utf-8',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
                'Accept-Language' => 'ru,en',
                'Connection' => 'keep-alive',
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/96.0.4664.110 Safari/537.36',
            ]
        ]);

        file_put_contents('crawler.html', $result->getContent());

        $crawler = new Crawler($result->getContent());
        $list = $crawler->filter('a')->each(function ($a){
            /** @var $a Crawler */
            return ['name' => $a->text(), 'link' => $a->attr('href')];
        });

        if(empty($list)) return [];

        $this->torrent = reset($list);

        $result = $this->httpClient->request('GET', $this->forumUrl . $this->torrent['link'], [
            'verify_peer' => false,
            'verify_host' => false,
            'headers' => [
                'Connection' => 'keep-alive',
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/96.0.4664.110 Safari/537.36',
            ]
        ]);

        $crawler = new Crawler($result->getContent());

        $this->torrent['name'] = $crawler->filter('.detail_tbl tr')->first()->filter('[itemprop="name"]')->first()->text();

        $table = $crawler->filter('#tbody_id2>tr:not(.expand-child)')->each(function ($tr) {
            return $tr->filter('td')->each(function ($td, $k) {
                /** @var Crawler $td */
                $text = trim($td->text());
                if($k == 4) {
                    return ['code' => 'link', 'value' => $td->filter('a')->attr('href')];
                } elseif($k == 3) {
                    return ['code' => 'size',  'value' => $text];
                } elseif($k == 1) {
                    return ['code' => 'name',  'value' => $this->torrent['name']];
                }
                return null;
            });
        });


        $result = [];
        foreach ($table as $k => $tr) {
            foreach ($tr as $td) {
                if($td) {
                    $result[$k][$td['code']] = $td['value'];
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
        return $this->transmission->add($url)->getId();
    }
}