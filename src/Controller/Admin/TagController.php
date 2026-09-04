<?php

namespace App\Controller\Admin;

use App\Entity\Tag;
use App\Form\TagType;
use App\Repository\Recipe\TagRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/admin/tags', name: 'admin.tag.')]
final class TagController extends AbstractController
{
    public function __construct(
        private readonly TagRepository $tagRepository,
        private readonly PaginatorInterface $paginator,
        #[Autowire('%number_per_page_tags%')]
        private readonly int $numberPerPageTags,
    ) {
    }

    #[Route('/', name: 'index')]
    public function index(Request $request): Response
    {
        $query = $this->tagRepository->findAllTagsByQueryBuilder();

        $pagination = $this->paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            $this->numberPerPageTags
        );

        return $this->render('admin/tag/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/create', name: 'create', methods: ['GET', 'POST'])]
    public function new(Request $request): RedirectResponse|Response
    {
        $tag = new Tag();
        $form = $this->createForm(TagType::class, $tag, [
            'attr' => [
                'novalidate' => 'novalidate',
            ],
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->tagRepository->save($tag, true);
            $this->addFlash('success', 'Le tag a bien été créé');

            return $this->redirectToRoute('admin.tag.index');
        }

        return $this->render('admin/tag/new.html.twig', [
            'form' => $form,
            'show' => false,
        ]);
    }

    #[Route('/{id}', name: 'edit', requirements: ['id' => Requirement::DIGITS], methods: ['GET', 'POST'])]
    public function edit(Tag $tag, Request $request): Response
    {
        $form = $this->createForm(TagType::class, $tag);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->tagRepository->save($tag, true);
            $this->addFlash('success', 'Le tag a bien été modifié');

            return $this->redirectToRoute('admin.tag.index');
        }

        return $this->render('admin/tag/edit.html.twig', [
            'tag' => $tag,
            'form' => $form,
            'show' => false,
        ]);
    }

    #[Route('/{id}', name: 'delete', requirements: ['id' => Requirement::DIGITS], methods: ['DELETE'])]
    public function delete(Request $request, Tag $tag): RedirectResponse
    {
        if ($this->isCsrfTokenValid('delete'.$tag->getId(), $request->request->getString('_token'))) {
            if ($tag->getRecipes()->count() > 0) {
                $this->addFlash('danger', 'Impossible de supprimer ce tag car il est lié à des recettes.');

                return $this->redirectToRoute('admin.tag.index');
            }

            $this->tagRepository->remove($tag, true);
            $this->addFlash('success', 'Tag supprimé avec succès');
        }

        return $this->redirectToRoute('admin.tag.index');
    }
}
