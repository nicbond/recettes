<?php

namespace App\MessageHandler;

use App\Entity\Recipe\Recipe;
use App\Message\RecipePDFMessage;
use App\Repository\Recipe\RecipeRepository;
use App\Service\ImageConversionService;
use Dompdf\Dompdf;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Twig\Environment;
use Vich\UploaderBundle\Storage\StorageInterface;

#[AsMessageHandler]
final readonly class RecipePDFMessageHandler
{
    public function __construct(
        #[Autowire('%app.recipe_pdf_path%')]
        private string $path,
        private LoggerInterface $logger,
        private RecipeRepository $recipeRepository,
        private Environment $twig,
        private StorageInterface $storage,
        private ImageConversionService $imageConversionService,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function __invoke(RecipePDFMessage $message): void
    {
        $recipe = $this->recipeRepository->find($message->recipeId);
        if (!$recipe instanceof Recipe) {
            $this->logger->error('RecipePDFMessage : Recipe not found', [
                'recipeId' => $message->recipeId,
            ]);
            throw new \Exception(sprintf('Recipe %s not found', $message->recipeId));
        }

        $imagePath = $this->storage->resolvePath($recipe, 'thumbnailFile');
        $imageBase64 = $imagePath ? $this->imageConversionService->convertToBase64($imagePath) : null;

        $html = $this->twig->render('admin/recipe/pdf.html.twig', [
            'recipe' => $recipe,
            'imageBase64' => $imageBase64,
        ]);

        // Generate PDF
        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // PDF save
        $filePath = $this->path.'/'.$message->recipeId.'.pdf';
        file_put_contents($filePath, $dompdf->output());

        $this->logger->info('RecipePDFMessage : PDF created for recipe {recipeId}', [
            'recipeId' => $message->recipeId,
        ]);
    }
}
