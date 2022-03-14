<?php

namespace App\Controller;

use App\Service\Telegram\TelegramClient;
use App\Service\Torrent\TorrentClientInterface;
use App\Service\Torrent\TransmissionClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Transmission\Model\Torrent;

class TelegramController extends AbstractController
{
    private TorrentClientInterface $client;
    private TransmissionClient $transmission;
    private TelegramClient $telegramClient;

    public function __construct(TorrentClientInterface $client, TransmissionClient $transmission, TelegramClient $telegramClient) {

        $this->client = $client;
        $this->transmission = $transmission;
        $this->telegramClient = $telegramClient;
    }

    /**
     * @Route("/api/telegram", name="telegram", methods={"POST"})
     */
    public function search() :?Response
    {
        $data = file_get_contents('php://input');
        $data = json_decode($data, true);
        $chat = $data['message']['chat']['id'];
        $q = $data['message']['text'];

        if($q == '?') {
            $list = $this->transmission->all();
            $done = true;
            foreach ($list as $torrent) {
                /** @var Torrent $torrent */
                if($torrent->isDownloading()) {
                    $done = false;
                    $name = $torrent->getName();
                    $percent = $torrent->getPercentDone();
                    $this->telegramClient->sendMess("Файл $name в процессе - $percent%", $chat);
                }
            }
            if($done) {
                $this->telegramClient->sendMess('Все уже скачалось!', $chat);
            }
        } elseif($q) {
            $this->telegramClient->sendMess('Ищем торрент: ' . $q, $chat);
            $torrents = $this->client->search($q);
            if(!$torrents) {
                $this->telegramClient->sendMess('Сорян, ничего не нашел(', $chat);
            } else {
                $this->telegramClient->sendMess('Выбираем подходящий файл', $chat);
                if($torrent = $this->telegramClient->chooseTorrent($torrents, $chat)) {
                    $this->telegramClient->sendMess(
                        'Торрент '.$torrent['name'] . ' ' . $torrent['size'] . ' Мб скачивается.',
                        $chat
                    );
                } else {
                    $this->telegramClient->sendMess('Не получилось ничего(', $chat);
                }
            }
        }
        return $this->json('');
    }
}
