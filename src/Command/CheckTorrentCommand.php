<?php

namespace App\Command;

use App\Service\Telegram\TelegramClient;
use App\Service\Torrent\TransmissionClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Transmission\Model\Torrent;

class CheckTorrentCommand extends Command
{
    protected static $defaultName = 'app:check-torrent';
    protected static $defaultDescription = 'Add a short description for your command';
    private TransmissionClient $transmissionClient;
    private TelegramClient $telegramClient;

    public function __construct(TransmissionClient $transmissionClient, TelegramClient $telegramClient)
    {
        parent::__construct(null);
        $this->transmissionClient = $transmissionClient;
        $this->telegramClient = $telegramClient;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        if($count = $this->check()) {
            $io->info('Завершена загрузка: ' . $count);
        } else {
            $io->info('Нет торрентов');
        }
        return Command::SUCCESS;
    }

    private function check(): int
    {
        $counter = 0;
        $list = $this->transmissionClient->all();
        $telegramQueue = $this->telegramClient->readQueue();
        foreach ($list as $torrent) {
            /** @var Torrent $torrent */
            $id = $torrent->getId();
            if(isset($telegramQueue[$id]) && $torrent->isFinished()) {
                $counter++;
                $chat = $telegramQueue[$id];
                $message = 'Файл ' . $torrent->getName() . ' скачался! Врубай ' . $this->telegramClient->smile('television');
                $this->telegramClient->sendMess($message, $chat);
                $this->telegramClient->removeFromQueue($id);
            }
        }
        return $counter;
    }
}
