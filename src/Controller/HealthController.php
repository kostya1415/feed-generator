<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class HealthController extends AbstractController
{
    #[Route(path: '/health', name: 'health', methods: 'GET')]
    public function health(): JsonResponse
    {
        return new JsonResponse(['health' => 'ok']);
    }
}