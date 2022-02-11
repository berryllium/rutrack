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
            $text = 'Что хочешь скачать, малыш?';
        } elseif($q = $requestArr['request']['command']) {
            $list = $client->search(urlencode($q));
            if(count($list)) {
                $torrent = reset($list);
                $result = $client->getMagnet($torrent['link']);
                $text = $result ?
                    'Поставила качаться ' . $torrent['name'] . ', весит ' . $torrent['size'] :
                    'Что-то пошло не туда';
            } else {
                $text = 'Ничего не смогла найти, попробуй еще разок!';
            }
        }
        return $this->json([
            'version' => $requestArr['version'],
            'session' => $requestArr['session'],
            'response' => [
                'end_session' => false,
                'text' => $text
            ]
        ]);
    }
}
