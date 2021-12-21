<?php

namespace App\Service\Torrent;

interface TorrentClientInterface
{

    /**
     * @return boolean
     */
    public function auth(): bool;

    /**
     * @param $q string поисковый запрос
     * @return array
     */
    public function search(string $q): array;

    /**
     * @param $url string
     * @return string|null
     */
    public function getMagnet(string $url): string;

}