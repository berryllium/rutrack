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
        try {
            if($q == '?') {
                $list = $this->transmission->all();
                $done = true;
                foreach ($list as $torrent) {
                    /** @var Torrent $torrent */
                    if($torrent->isDownloading()) {
                        $done = false;
                        $name = $torrent->getName();
                        $percent = $torrent->getPercentDone();
                        $this->telegramClient->sendMess($this->telegramClient->smile('clock') . " $name в процессе - $percent%", $chat);
                    }
                }
                if($done) {
                    $this->telegramClient->sendMess('Все уже скачалось!', $chat);
                }
                return $this->json('OK');
            } elseif($q) {
                $this->telegramClient->sendMess($this->telegramClient->smile('telescope') . ' Ищем торрент: ' . $q, $chat);
                $torrents = $this->client->search($q);
                if($torrents === null) {
                    $this->telegramClient->sendMess('Беда с авторизацией, опять сервис заблокировали(', $chat);
                }elseif(!$torrents) {
                    $this->telegramClient->sendMess('Сорян, ничего не нашел', $chat);
                    $this->telegramClient->sendMess($this->telegramClient->smile('sweat'), $chat);
                } else {
                    $this->telegramClient->sendMess('Выбираем подходящий файл', $chat);
                    if($torrent = $this->telegramClient->chooseTorrent($torrents, $chat)) {
                        $this->telegramClient->sendMess(
                            $this->telegramClient->smile('clock') . ' Торрент '.$torrent['name'] . ' скачивается.' . ' Размер: ' . $torrent['size'] . ' Мб',
                            $chat
                        );
                    } else {
                        $this->telegramClient->sendMess('Не получилось ничего', $chat);
                        $this->telegramClient->sendMess($this->telegramClient->smile('cry'), $chat);
                    }
                }
                return $this->json('OK');
            }
        } catch (\Exception $exception) {
            $this->telegramClient->sendMess('Не получилось ничего( ' . $exception, $chat);
            return $this->json('OK');
        }

    }
}
