<?php

namespace App\Controller;

use App\Service\Torrent\TorrentClientInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AlisaController extends AbstractController
{
    /**
     * @Route("/api/alisa", name="alisa", methods={"POST"})
     */
    public function index(Request $request, TorrentClientInterface $client): Response
    {
        $requestArr = $request->toArray();
        $text = '';
        if($requestArr['session']['new']) {
            $end_session = false;
            $text = 'Что хочешь скачать, малыш?';
        } elseif($q = $requestArr['request']['command']) {
            $end_session = true;
            $list = $client->search(urlencode($q));
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
                    $text = 'Нет адекватного размера';
                } else {
                    $torrent = reset($list);
                    $result = $client->getMagnet($torrent['link']);
                    $text = $result ?
                        'Поставила качаться ' . $torrent['name'] . ', весит ' . $torrent['size'] . ' Гигабайт':
                        'Что-то пошло не туда';
                }
            } else {
                $text = 'Ничего не смогла найти, попробуй еще разок!';
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
