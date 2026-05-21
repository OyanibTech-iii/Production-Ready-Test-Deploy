<?php

namespace App\Controller;

use App\Entity\AnswerChoice;
use App\Form\AnswerChoiceType;
use App\Repository\AnswerChoiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/answer/choice')]
final class AnswerChoiceController extends AbstractController
{
    #[Route(name: 'app_answer_choice_index', methods: ['GET'])]
    public function index(AnswerChoiceRepository $answerChoiceRepository): Response
    {
        return $this->render('answer_choice/index.html.twig', [
            'answer_choices' => $answerChoiceRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_answer_choice_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $answerChoice = new AnswerChoice();
        $form = $this->createForm(AnswerChoiceType::class, $answerChoice);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($answerChoice);
            $entityManager->flush();

            return $this->redirectToRoute('app_answer_choice_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('answer_choice/new.html.twig', [
            'answer_choice' => $answerChoice,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_answer_choice_show', methods: ['GET'])]
    public function show(AnswerChoice $answerChoice): Response
    {
        return $this->render('answer_choice/show.html.twig', [
            'answer_choice' => $answerChoice,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_answer_choice_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, AnswerChoice $answerChoice, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(AnswerChoiceType::class, $answerChoice);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_answer_choice_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('answer_choice/edit.html.twig', [
            'answer_choice' => $answerChoice,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_answer_choice_delete', methods: ['POST'])]
    public function delete(Request $request, AnswerChoice $answerChoice, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$answerChoice->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($answerChoice);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_answer_choice_index', [], Response::HTTP_SEE_OTHER);
    }
}
