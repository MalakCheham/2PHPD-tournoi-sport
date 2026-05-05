<?php

namespace App\Controller;

use App\Entity\Tournament;
use App\Entity\User;
use App\Event\TournamentWonEvent;
use App\Repository\TournamentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[Route('/api/tournaments')]
class TournamentController extends AbstractController
{
    #[Route('', name: 'tournaments_list', methods: ['GET'])]
    public function list(TournamentRepository $repo): JsonResponse
    {
        $tournaments = $repo->findAll();
        $data = [];

        foreach ($tournaments as $tournament) {
            $data[] = $this->formatTournament($tournament);
        }

        return $this->json($data);
    }

    #[Route('', name: 'tournaments_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['tournamentName']) || empty($data['startDate']) || empty($data['endDate']) || empty($data['description'])) {
            return $this->json(['error' => 'Champs manquants'], 400);
        }

        $tournament = new Tournament();
        $tournament->setTournamentName($data['tournamentName']);
        $tournament->setStartDate(new \DateTime($data['startDate']));
        $tournament->setEndDate(new \DateTime($data['endDate']));
        $tournament->setDescription($data['description']);
        $tournament->setLocation($data['location'] ?? null);
        $tournament->setMaxParticipants($data['maxParticipants'] ?? 0);
        $tournament->setSport($data['sport'] ?? '');
        $tournament->setOrganizer($this->getUser());

        $em->persist($tournament);
        $em->flush();

        return $this->json($this->formatTournament($tournament), 201);
    }

    #[Route('/{id}', name: 'tournaments_show', methods: ['GET'])]
    public function show(Tournament $tournament): JsonResponse
    {
        return $this->json($this->formatTournament($tournament));
    }

    #[Route('/{id}', name: 'tournaments_update', methods: ['PUT'])]
    public function update(Tournament $tournament, Request $request, EntityManagerInterface $em, EventDispatcherInterface $eventDispatcher): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (isset($data['tournamentName'])) $tournament->setTournamentName($data['tournamentName']);
        if (isset($data['startDate'])) $tournament->setStartDate(new \DateTime($data['startDate']));
        if (isset($data['endDate'])) $tournament->setEndDate(new \DateTime($data['endDate']));
        if (isset($data['description'])) $tournament->setDescription($data['description']);
        if (isset($data['location'])) $tournament->setLocation($data['location']);
        if (isset($data['maxParticipants'])) $tournament->setMaxParticipants($data['maxParticipants']);
        if (isset($data['sport'])) $tournament->setSport($data['sport']);

        // Si on définit un gagnant, on déclenche l'événement
        if (isset($data['winnerId'])) {
            $winner = $em->getRepository(User::class)->find($data['winnerId']);
            if ($winner) {
                $tournament->setWinner($winner);
                $eventDispatcher->dispatch(new TournamentWonEvent($tournament, $winner), TournamentWonEvent::NAME);
            }
        }

        $em->flush();

        return $this->json($this->formatTournament($tournament));
    }

    #[Route('/{id}', name: 'tournaments_delete', methods: ['DELETE'])]
    public function delete(Tournament $tournament, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($tournament);
        $em->flush();

        return $this->json(['message' => 'Tournoi supprimé'], 200);
    }

    private function formatTournament(Tournament $tournament): array
    {
        $now = new \DateTime();
        $start = $tournament->getStartDate();
        $end = $tournament->getEndDate();

        if ($now < $start) {
            $status = 'à venir';
        } elseif ($now > $end) {
            $status = 'terminé';
        } else {
            $status = 'en cours';
        }

        return [
            'id' => $tournament->getId(),
            'tournamentName' => $tournament->getTournamentName(),
            'startDate' => $tournament->getStartDate()->format('Y-m-d'),
            'endDate' => $tournament->getEndDate()->format('Y-m-d'),
            'location' => $tournament->getLocation(),
            'description' => $tournament->getDescription(),
            'maxParticipants' => $tournament->getMaxParticipants(),
            'sport' => $tournament->getSport(),
            'status' => $status,
            'organizer' => $tournament->getOrganizer()?->getId(),
            'winner' => $tournament->getWinner()?->getId(),
        ];
    }
}