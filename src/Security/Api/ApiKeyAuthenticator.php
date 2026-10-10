<?php

declare(strict_types=1);

namespace App\Security\Api;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

final class ApiKeyAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        #[Autowire('%env(string:PUBLIC_API_KEY)%')] private readonly string $apiKey)
    {
    }

    public function supports(Request $request): bool
    {
        return $request->headers->has('X-AUTH-API-KEY');
    }

    public function authenticate(Request $request): SelfValidatingPassport
    {
        $providedApiKey = $request->headers->get('X-AUTH-API-KEY');

        if (!\is_string($providedApiKey) || '' === $providedApiKey) {
            throw new AuthenticationException('Missing API Key.');
        }

        if (!hash_equals($this->apiKey, $providedApiKey)) {
            throw new AuthenticationException('Invalid API Key.');
        }

        return new SelfValidatingPassport(
            new UserBadge(
                'api-client',
                static fn (): ApiClient => new ApiClient('api-client'),
            ),
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        return new Response('Unauthorized', Response::HTTP_UNAUTHORIZED);
    }
}
