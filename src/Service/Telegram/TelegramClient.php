<?php

namespace App\Service\Telegram;


use App\Service\Torrent\TorrentClientInterface;

class TelegramClient
{
    private string $token;
    private array $queue;
    private string $fileQueue;
    private TorrentClientInterface $torrentClient;
    private $default_chat;

    public function __construct($token, $default_chat, TorrentClientInterface $lafaClient) {
        $this->token = $token;
        $this->torrentClient = $lafaClient;
        $this->fileQueue = dirname(__DIR__, 3) . '/var/telegramTorrentQueue.txt';
        $this->readQueue();
        $this->default_chat = $default_chat;
    }

    public function sendMess($message, $chat): bool
    {
        $data = [
            'text' => $message,
            'chat_id' => $chat
        ];

        $ch = curl_init('https://api.telegram.org/bot' . $this->token . '/sendMessage');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $http_code == 200;
    }

    public function chooseTorrent($torrents, $chat) {

        foreach ($torrents as $torrent) {
            $size = (float) preg_replace('#[^0-9\.]#', '', $torrent['size']);
            if(strpos($torrent['size'], 'GB') !== false) {
                $size *= 1000;
            }
            if($size >= 1000 && $size <= 5000) {
                if($id = $this->torrentClient->getMagnet($torrent['link'])) {
                    $this->addToQueue($id, $chat);
                    $torrent['id'] = $id;
                    $torrent['size'] = $size;
                    return $torrent;
                }
            }
        }
        return false;
    }

    public function addToQueue(string $id, $chat = false)
    {
        $chat = $chat ?: $this->default_chat;
        if(!$chat || !$id) return false;
        $this->queue[$id] = $chat;
        $this->writeQueue();
    }

    public function removeFromQueue($id) {
        unset($this->queue[$id]);
        $this->writeQueue();
    }

    private function writeQueue()
    {
        return file_put_contents($this->fileQueue, serialize($this->queue));
    }

    public function readQueue() {
        if(file_exists($this->fileQueue)) {
            $this->queue = unserialize(file_get_contents($this->fileQueue));
        } else {
            $this->queue = [];
        }
        return $this->queue;
    }

}