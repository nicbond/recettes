<?php

namespace App\OpenApi\Security;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\OpenApi;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;

#[AsDecorator(decorates: 'api_platform.openapi.factory')]
final class SecurityOpenApiDecorator implements OpenApiFactoryInterface
{
    public function __construct(private readonly OpenApiFactoryInterface $decorated)
    {
    }

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);

        // Global security is removed by default to prevent the inheritance of the padlock icon.
        $openApi = $openApi->withSecurity([]);

        $paths = $openApi->getPaths();

        foreach ($paths->getPaths() as $path => $pathItem) {
            foreach (['Get', 'Post', 'Put', 'Patch', 'Delete'] as $method) {
                $getter = 'get'.$method;
                $wither = 'with'.$method;

                if (!$operation = $pathItem->$getter()) {
                    continue;
                }

                if ($this->isPublicAccess($path, $method)) {
                    $pathItem = $pathItem->$wither($operation->withSecurity(null));
                    continue;
                }

                if (str_starts_with($path, '/api/public')) {
                    $pathItem = $pathItem->$wither($operation->withSecurity([['ApiKey' => []]]));
                    continue;
                }

                $pathItem = $pathItem->$wither($operation->withSecurity([['JWT' => []]]));
            }

            $paths->addPath($path, $pathItem);
        }

        return $openApi;
    }

    private function isPublicAccess(string $path, string $method): bool
    {
        if ('/api/login_check' === $path && 'Post' === $method) {
            return true;
        }

        if (in_array($path, ['/api/consumers', '/api/consumers/activate'], true) && 'Post' === $method) {
            return true;
        }

        return false;
    }
}
