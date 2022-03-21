<?php

namespace App\Controller;

use App\Service\RuCaptcha;
use App\Service\Torrent\RutrackerClient;
use App\Service\Torrent\TorrentClientInterface;
use App\Service\Torrent\TransmissionClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Transmission\Client;
use Transmission\Transmission;

class ApiController extends AbstractController
{
    /**
     * @Route("/api", name="api")
     */
    public function index(RuCaptcha $captcha): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/ApiController.php'
        ]);
    }

    /**
     * @Route("/api/search", name="api_search")
     * @return JsonResponse
     */
    public function search(Request $request, TorrentClientInterface $client) : JsonResponse{
        $q = $request->get('q');
        $result = $client->search($q);
        return $this->json($result, 200, ['Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0']);
    }

    /**
     * @Route("/api/download", name="api_download")
     * @return JsonResponse
     */
    public function download(Request $request, TorrentClientInterface $client) : JsonResponse{

        $link = $request->get('link');
        $result = $client->getMagnet($link);
        $status = $result ? 'success' : 'error';
        $message = $result ? 'Торрент успешно добавлен!' : 'Ошибка';
        return $this->json([
            'result' => $result,
            'status' => $status,
            'message' => $message,
        ]);
    }
}
