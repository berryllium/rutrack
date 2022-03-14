<?php

namespace App\Controller;

use App\Service\Torrent\TorrentClientInterface;
use App\Service\Torrent\TransmissionClient;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Transmission\Model\Torrent;

class TelegramController extends AbstractController
{
    private TorrentClientInterface $client;
    private LoggerInterface $logger;
    private TransmissionClient $transmission;

    public function __construct(TorrentClientInterface $client, LoggerInterface $logger, TransmissionClient $transmission) {

        $this->client = $client;
        $this->logger = $logger;
        $this->transmission = $transmission;
    }

    private string $token = '5267750421:AAGTso1usH3TOiv575utKFEO4vwsXepj9zo';

    /**
     * @Route("/api/telegram", name="telegram", methods={"POST"})
     */
    public function search() :Response
    {
        $data = file_get_contents('php://input');
        $data = json_decode($data, true);
        $chat = $data['message']['chat']['id'];
        $q = $data['message']['text'];

        if($q == 'и') {
            $list = $this->transmission->all();
            foreach ($list as $torrent) {
                /** @var Torrent $torrent */
                $status = $torrent->getStatus();
                if($status == 4) {
                    $this->sendMess([
                        'chat_id' => $chat,
                        'text' => 'Файл в процессе ' . $torrent->getPercentDone() . '%'
                    ]);
                    return $this->json(['status' => 'OK']);
                }
            }
            $this->sendMess([
                'chat_id' => $chat,
                'text' => 'Все уже скачалось!'
            ]);
        } elseif($q) {
            $this->sendMess([
                'chat_id' => $chat,
                'text' => 'Ищем торрент: ' . $q . ' ' . date('H:i:s')
            ]);

            $torrents = $this->client->search($q);

            if(!$torrents) {
                $this->sendMess([
                    'chat_id' => $chat,
                    'text' => 'Сорян, ничего не нашел('
                ]);
            } else {
                $this->sendMess([
                    'chat_id' => $chat,
                    'text' => 'Выбираем подходящий файл'
                ]);

                if($torrent = $this->checkSize($torrents)) {
                    $this->sendMess([
                        'chat_id' => $chat,
                        'text' => 'Торрент '.$torrent['name'] . ' ' . $torrent['size'] . ' Мб скачивается.'
                    ]);
                } else {
                    $this->sendMess([
                        'chat_id' => $chat,
                        'text' => "Не получилось ничего("
                    ]);
                }

            }
        }
        return $this->json(['status' => 'OK']);
    }

    public function sendMess($message) {
        $ch = curl_init('https://api.telegram.org/bot' . $this->token . '/sendMessage');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $message);
        curl_exec($ch);
        curl_close($ch);
    }

    private function checkSize($torrents) {

        foreach ($torrents as $torrent) {
            $size = (float) preg_replace('#[^0-9\.]#', '', $torrent['size']);
            if(strpos($torrent['size'], 'GB') !== false) {
                $size *= 1000;
            }

            if($size >= 1024 && $size <= 5000) {
                $this->client->getMagnet($torrent['link']);
                $torrent['size'] = $size;
                return $torrent;
            }
        }
        return false;
    }

}
