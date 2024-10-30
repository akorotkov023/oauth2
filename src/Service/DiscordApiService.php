<?php

namespace App\Service;

use App\Model\DiscordUser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class DiscordApiService
{

    const AUTORIZATION_URI = 'https://discord.com/oauth2/authorize';
    const USERS_ME_ENDPOINT = '/api/users/@me';

    public function __construct(
        private readonly HttpClientInterface $discordApiClient,
        private readonly SerializerInterface $serializer,
        private readonly string $clientId,
        private readonly string $redirectUri
    )
    {}

    public function getAuthUrl(array $scope): string
    {
        $params = http_build_query([
           'client_id' => $this->clientId,
           'redirect_uri' => $this->redirectUri,
           'response_type' => 'token',
           'scope' => implode(' ', $scope),
           'prompt' => 'none'

        ]);

        return self::AUTORIZATION_URI . '?' . $params;
    }

    public function fetchUser(string $accessToken)
    {
        $response = $this->discordApiClient->request(Request::METHOD_GET, self::USERS_ME_ENDPOINT, [
            'auth_bearer' => $accessToken
        ]);

        $data = $response->getContent();

        return $this->serializer->deserialize($data, DiscordUser::class, 'json');
    }
}
