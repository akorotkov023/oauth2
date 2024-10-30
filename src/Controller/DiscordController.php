<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\DiscordAuthenticator;
use App\Service\DiscordApiService;
use Doctrine\ORM\EntityManagerInterface;
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
            $request->getSession()->set(DiscordAuthenticator::DISCORD_AUTH_KEY, true);
            $scope = ['identify', 'email'];

            return $this->redirect($this->discordApiService->getAuthUrl($scope));
        }

        return $this->redirectToRoute('app_home');
    }

    #[Route('/discord/auth', name: 'app_discord_auth')]
    public function auth(): Response
    {
        return $this->redirectToRoute('app_home');
    }

    #[Route('/discord/check', name: 'app_discord_check')]
    public function check(Request $request, EntityManagerInterface $em, UserRepository $userRepo): Response
    {

        $accessToken = $request->get('access_token');

        if (!$accessToken){
            return $this->render('discord/check.html.twig');
        }


        $discordUser = $this->discordApiService->fetchUser($accessToken);

        $user = $userRepo->findOneBy(['discordId' => $discordUser->id]);

        if ($user) {
            return $this->redirectToRoute('app_discord_auth', [
                'accessToken' => $accessToken
            ]);
        }

        $user = new User();

        $user->setAccessToken($accessToken);
        $user->setUsername($discordUser->username);
        $user->setEmail($discordUser->email);
        $user->setAvatar(isset($discordUser->avatar) ?? '');
        $user->setDiscordId($discordUser->id);

        $em->persist($user);
        $em->flush();

        return $this->redirectToRoute('app_discord_auth', [
            'accessToken' => $accessToken
        ]);

    }
}
