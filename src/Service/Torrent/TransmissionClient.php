<?php

namespace App\Service\Torrent;

use Transmission\Client;
use Transmission\Transmission;

class TransmissionClient
{
    private $transmission;
    private $host;
    private $port;
    private $login;
    private $password;

    public function __construct($host, $port, $login, $password) {
        $this->host = $host;
        $this->port = $port;
        $this->login = $login;
        $this->password = $password;

        $client = new Client($this->host, $this->port);
        $client->authenticate($this->login, $this->password);
        $this->transmission = new Transmission();
        $this->transmission->setClient($client);
    }

    public function add($link): \Transmission\Model\Torrent
    {
        return $this->transmission->add($link);
    }

    public function get($id): \Transmission\Model\Torrent
    {
        return $this->transmission->get($id);
    }

    public function all(): array
    {
        return $this->transmission->all();
    }
}