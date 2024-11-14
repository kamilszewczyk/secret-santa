<?php

namespace App\Controller;

use App\Entity\Member;
use Doctrine\Persistence\ManagerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\TextEditorType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class WishlistController extends AbstractController
{
    public function __construct(
        private ManagerRegistry $doctrine
    ) {
    }

    #[Route('/wishlist/{name}', name: 'app_wishlist')]
    public function index(string $name): Response {
        $member = $this->doctrine->getRepository(Member::class)->findOneBy(['name' => $name]);

        if (!$member) {
            throw $this->createNotFoundException('The member does not exist');
        }

        return $this->render('wishlist/index.html.twig', [
            'name' => $member->getName(),
            'wishlist' => $member->getWishlist(),
        ]);
    }

    #[Route('/wishlist/edit/{uuid}', name: 'app_wishlist_edit')]
    public function edit(Request $request, string $uuid): Response {
        $member = $this->doctrine->getRepository(Member::class)->findOneBy(['uuid' => $uuid]);

        if (!$member) {
            throw $this->createNotFoundException('The member does not exist');
        }

        $form = $this->createFormBuilder($member)
             ->add('wishlist', TextareaType::class, ['label' => 'Lista życzeń'])
             ->add('save', SubmitType::class, ['label' => 'Zapisz'])
             ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $member = $form->getData();

            $this->doctrine->getRepository(Member::class)->save($member, true);

            return $this->redirectToRoute('app_wishlist_edit', ['uuid' => $uuid]);
        }

        return $this->render('wishlist/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
