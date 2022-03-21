<?php

namespace App\Controller;

use App\Service\Telegram\TelegramClient;
use App\Service\Torrent\TorrentClientInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AlisaController extends AbstractController
{
    /**
     * @Route("/api/alisa", name="alisa", methods={"POST"})
     */
    public function index(Request $request, TorrentClientInterface $client, TelegramClient $telegram): Response
    {
        $requestArr = $request->toArray();
        $end_session = false;
        $text = 'Ну, здравствуй, Джек, мать твою, воробей! Что для тебя скачать торрентов, бедолага?';

        if(!$requestArr['session']['new'] && ($q = $requestArr['request']['command'])) {
            $end_session = true;
            $list = $client->search($q);
            if(count($list)) {

                foreach ($list as $key => &$item) {
                    $k = 0;
                    if (strpos($item['size'], 'МB') !== false) {
                        $k = 1;
                    } elseif (strpos($item['size'], 'GB') !== false) {
                        $k = 1024;
                    }

                    $size = $k * (float) preg_replace('#[^0-9\.]#', '', $item['size']);
                    if($size < 1000 || $size > 7000) {
                        unset($list[$key]);
                    } else {
                        $item['size'] = round($size/1024, 1);
                    }
                }
                if(!count($list)) {
                    $text = 'Нет адекватного размера, все файлы какие-то подозрительные';
                } else {
                    $torrent = reset($list);
                    if($id = $client->getMagnet($torrent['link'])){
                        $telegram->addToQueue($id);
                        $text = 'Пошла жаришка, файл качается, ' . $torrent['name'] . ', весит ' .
                            $torrent['size'] . ' Гигабайт';
                    } else {
                        $text = 'Что-то пошло не туда';
                    }
                }
            } else {
                $text = 'Ничего не получилось найти, попробуй еще разок!';
            }
        }
        return $this->json([
            'version' => $requestArr['version'],
            'session' => $requestArr['session'],
            'response' => [
                'end_session' => $end_session,
                'text' => $text
            ]
        ]);
    }
}
