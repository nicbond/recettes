<?php

namespace App\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use App\Controller\Api\MeController;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[AsDecorator(decorates: 'api_platform.openapi.factory')]
final readonly class MeOpenApiFactory implements OpenApiFactoryInterface
{
    public function __construct(
        #[AutowireDecorated]
        private OpenApiFactoryInterface $decorated,
    ) {
    }

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);

        $openApi->getPaths()->addPath('/api/me', new PathItem(
            get: new Operation(
                operationId: 'api_me_get',
                tags: ['Consumer'],
                responses: [
                    '200' => new Response(description: 'Authenticated consumer, with their identity'),
                    '401' => new Response(description: 'JWT missing, invalid, or expired'),
                    '403' => new Response(description: 'Required role missing'),
                ],
                summary: 'Returns the connected consumer',
                description: sprintf('Requires a valid JWT and the role **%s**.', $this->requiredRole()),
                security: [['JWT' => []]],
            ),
        ));

        return $openApi;
    }

    private function requiredRole(): string
    {
        $attributes = (new \ReflectionMethod(MeController::class, '__invoke'))
            ->getAttributes(IsGranted::class);

        $attribute = isset($attributes[0]) ? $attributes[0]->newInstance()->attribute : null;

        return is_string($attribute) ? $attribute : 'ROLE_CONSUMER';
    }
}
