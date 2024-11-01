<?php

namespace App\Security;

use App\Model\DiscordUser;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Translation\Exception\RuntimeException;


class DiscordAuthenticator extends AbstractAuthenticator
{

    const DISCORD_AUTH_KEY = 'discord-auth';

    public function __construct(
        private readonly DiscordUser $discordUser,
        private readonly UserRepository $userRepository,
    ){}

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'app_discord_auth' && $this->isValidRequest($request);
    }

    public function authenticate(Request $request): Passport
    {
        if (!$this->isValidRequest($request)) {
            throw new RuntimeExceptions('Error request');
        }

        $accessToken = $request->query->get('accessToken');

        if (!$accessToken) {
            throw new RuntimeExceptions('Error request');
        }

        $user = $this->userRepository->findOneBy(['accessToken' => $accessToken]);

        if (!$user) {
            throw new RuntimeExceptions('Wrong access token');
        }

        $userBadge = new UserBadge($user->getUserIdentifier(), function() use ($user) {
            return $user;
        });

        return new SelfValidatingPassport($userBadge);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $request->getSession()->remove(self::DISCORD_AUTH_KEY);

        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        /** @var Session $session */
        $session = $request->getSession();
        $session->remove(self::DISCORD_AUTH_KEY);
        $session->getFlashBag()->set('danger', $exception->getMessageData());


        return new RedirectResponse('app_home');
    }

    private function isValidRequest(Request $request): bool
    {
        return true == $request->getSession()->get(self::DISCORD_AUTH_KEY);
    }
}
