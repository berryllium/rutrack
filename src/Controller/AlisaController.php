<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AlisaController extends AbstractController
{
    /**
     * @Route("/alisa", name="alisa")
     */
    public function index(): Response
    {
        dd('test');
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/AlisaController.php',
        ]);
    }
}
