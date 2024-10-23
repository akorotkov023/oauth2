<?php

namespace App\Controller;

use App\Service\DiscordApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DiscordController extends AbstractController
{

    public function __construct(
        private readonly DiscordApiService $discordApiService
    ){}

    #[Route('/discord/connect', name: 'app_discord_connect')]
    public function index(Request $request): Response
    {
        $token = $request->request->get('token');

        if ($this->isCsrfTokenValid('discord-auth', $token)){
            $request->getSession()->set('discord-auth', true);
            $scope = ['identify', 'email'];
            return $this->redirect($this->discordApiService->getAuthUrl($scope));
        }

        return $this->redirect('app_home');
    }

    #[Route('/discord/check', name: 'app_discord_check')]
    public function check(Request $request): Response
    {
        $accessToken = $request->get('access_token');

        if (!$accessToken){
            return $this->render('discord/check.html.twig');
        }


    }
}
