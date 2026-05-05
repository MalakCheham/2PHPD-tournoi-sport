<?php

namespace App\Controller;

use App\Entity\Registration;
use App\Repository\RegistrationRepository;
use App\Repository\TournamentRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/tournaments/{id}/registrations')]
class RegistrationController extends AbstractController
{
    #[Route('', name: 'registrations_list', methods: ['GET'])]
    public function list(int $id, TournamentRepository $tournamentRepo, RegistrationRepository $registrationRepo): JsonResponse
    {
        $tournament = $tournamentRepo->find($id);

        if (!$tournament) {
            return $this->json(['error' => 'Tournoi non trouvé'], 404);
        }

        $registrations = $registrationRepo->findBy(['tournament' => $tournament]);
        $data = [];

        foreach ($registrations as $registration) {
            $data[] = $this->formatRegistration($registration);
        }

        return $this->json($data);
    }

    #[Route('', name: 'registrations_create', methods: ['POST'])]
    public function create(int $id, Request $request, TournamentRepository $tournamentRepo, UserRepository $userRepo, EntityManagerInterface $em): JsonResponse
    {
        $tournament = $tournamentRepo->find($id);

        if (!$tournament) {
            return $this->json(['error' => 'Tournoi non trouvé'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (empty($data['playerId'])) {
            return $this->json(['error' => 'playerId manquant'], 400);
        }

        $player = $userRepo->find($data['playerId']);

        if (!$player) {
            return $this->json(['error' => 'Joueur non trouvé'], 404);
        }

        $registration = new Registration();
        $registration->setPlayer($player);
        $registration->setTournament($tournament);
        $registration->setRegistrationDate(new \DateTime());
        $registration->setStatus('en attente');

        $em->persist($registration);
        $em->flush();

        return $this->json($this->formatRegistration($registration), 201);
    }

    #[Route('/{idRegistration}', name: 'registrations_delete', methods: ['DELETE'])]
    public function delete(int $id, int $idRegistration, TournamentRepository $tournamentRepo, RegistrationRepository $registrationRepo, EntityManagerInterface $em): JsonResponse
    {
        $tournament = $tournamentRepo->find($id);

        if (!$tournament) {
            return $this->json(['error' => 'Tournoi non trouvé'], 404);
        }

        $registration = $registrationRepo->find($idRegistration);

        if (!$registration || $registration->getTournament()->getId() !== $id) {
            return $this->json(['error' => 'Inscription non trouvée'], 404);
        }

        $em->remove($registration);
        $em->flush();

        return $this->json(['message' => 'Inscription annulée'], 200);
    }

    private function formatRegistration(Registration $registration): array
    {
        return [
            'id' => $registration->getId(),
            'player' => $registration->getPlayer()->getId(),
            'tournament' => $registration->getTournament()->getId(),
            'registrationDate' => $registration->getRegistrationDate()->format('Y-m-d'),
            'status' => $registration->getStatus(),
        ];
    }
}