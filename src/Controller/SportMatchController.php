<?php

namespace App\Controller;

use App\Entity\SportMatch;
use App\Event\ScoreUpdatedEvent;
use App\Repository\RegistrationRepository;
use App\Repository\SportMatchRepository;
use App\Repository\TournamentRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[Route('/api/tournaments/{idTournament}/sport-matchs')]
class SportMatchController extends AbstractController
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher
    ) {}

    #[Route('', name: 'sport_matchs_list', methods: ['GET'])]
    public function list(int $idTournament, TournamentRepository $tournamentRepo, SportMatchRepository $sportMatchRepo): JsonResponse
    {
        $tournament = $tournamentRepo->find($idTournament);

        if (!$tournament) {
            return $this->json(['error' => 'Tournoi non trouvé'], 404);
        }

        $matches = $sportMatchRepo->findBy(['tournament' => $tournament]);
        $data = [];

        foreach ($matches as $match) {
            $data[] = $this->formatMatch($match);
        }

        return $this->json($data);
    }

    #[Route('', name: 'sport_matchs_create', methods: ['POST'])]
    public function create(int $idTournament, Request $request, TournamentRepository $tournamentRepo, UserRepository $userRepo, RegistrationRepository $registrationRepo, EntityManagerInterface $em): JsonResponse
    {
        $tournament = $tournamentRepo->find($idTournament);

        if (!$tournament) {
            return $this->json(['error' => 'Tournoi non trouvé'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (empty($data['player1Id']) || empty($data['player2Id'])) {
            return $this->json(['error' => 'player1Id et player2Id sont requis'], 400);
        }

        $player1 = $userRepo->find($data['player1Id']);
        $player2 = $userRepo->find($data['player2Id']);

        if (!$player1 || !$player2) {
            return $this->json(['error' => 'Joueur non trouvé'], 404);
        }

        $reg1 = $registrationRepo->findOneBy(['player' => $player1, 'tournament' => $tournament, 'status' => 'confirmée']);
        $reg2 = $registrationRepo->findOneBy(['player' => $player2, 'tournament' => $tournament, 'status' => 'confirmée']);

        if (!$reg1 || !$reg2) {
            return $this->json(['error' => 'Les deux joueurs doivent avoir une inscription confirmée'], 400);
        }

        $match = new SportMatch();
        $match->setTournament($tournament);
        $match->setPlayer1($player1);
        $match->setPlayer2($player2);
        $match->setMatchDate(new \DateTime($data['matchDate'] ?? 'now'));
        $match->setStatus('en attente');

        $em->persist($match);
        $em->flush();

        return $this->json($this->formatMatch($match), 201);
    }

    #[Route('/{idSportMatchs}', name: 'sport_matchs_show', methods: ['GET'])]
    public function show(int $idTournament, int $idSportMatchs, TournamentRepository $tournamentRepo, SportMatchRepository $sportMatchRepo): JsonResponse
    {
        $tournament = $tournamentRepo->find($idTournament);

        if (!$tournament) {
            return $this->json(['error' => 'Tournoi non trouvé'], 404);
        }

        $match = $sportMatchRepo->find($idSportMatchs);

        if (!$match || $match->getTournament()->getId() !== $idTournament) {
            return $this->json(['error' => 'Match non trouvé'], 404);
        }

        return $this->json($this->formatMatch($match));
    }

    #[Route('/{idSportMatchs}', name: 'sport_matchs_update', methods: ['PUT'])]
    public function update(int $idTournament, int $idSportMatchs, Request $request, TournamentRepository $tournamentRepo, SportMatchRepository $sportMatchRepo, EntityManagerInterface $em): JsonResponse
    {
        $tournament = $tournamentRepo->find($idTournament);

        if (!$tournament) {
            return $this->json(['error' => 'Tournoi non trouvé'], 404);
        }

        $match = $sportMatchRepo->find($idSportMatchs);

        if (!$match || $match->getTournament()->getId() !== $idTournament) {
            return $this->json(['error' => 'Match non trouvé'], 404);
        }

        $data = json_decode($request->getContent(), true);
        $currentUser = $this->getUser();
        $isAdmin = in_array('ROLE_ADMIN', $currentUser->getRoles());

        if (isset($data['scorePlayer1'])) {
            if (!$isAdmin && $match->getPlayer1()->getEmailAddress() !== $currentUser->getUserIdentifier()) {
                return $this->json(['error' => 'Non autorisé à modifier ce score'], 403);
            }
            $match->setScorePlayer1($data['scorePlayer1']);

            // Déclenche l'événement seulement si c'est un joueur (pas un admin)
            if (!$isAdmin) {
                $this->eventDispatcher->dispatch(new ScoreUpdatedEvent($match, $currentUser), ScoreUpdatedEvent::NAME);
            }
        }

        if (isset($data['scorePlayer2'])) {
            if (!$isAdmin && $match->getPlayer2()->getEmailAddress() !== $currentUser->getUserIdentifier()) {
                return $this->json(['error' => 'Non autorisé à modifier ce score'], 403);
            }
            $match->setScorePlayer2($data['scorePlayer2']);

            if (!$isAdmin) {
                $this->eventDispatcher->dispatch(new ScoreUpdatedEvent($match, $currentUser), ScoreUpdatedEvent::NAME);
            }
        }

        // Si les deux scores sont remplis, on termine le match
        if ($match->getScorePlayer1() !== null && $match->getScorePlayer2() !== null) {
            $match->setStatus('terminé');
        }

        $em->flush();

        return $this->json($this->formatMatch($match));
    }

    #[Route('/{idSportMatchs}', name: 'sport_matchs_delete', methods: ['DELETE'])]
    public function delete(int $idTournament, int $idSportMatchs, TournamentRepository $tournamentRepo, SportMatchRepository $sportMatchRepo, EntityManagerInterface $em): JsonResponse
    {
        $tournament = $tournamentRepo->find($idTournament);

        if (!$tournament) {
            return $this->json(['error' => 'Tournoi non trouvé'], 404);
        }

        $match = $sportMatchRepo->find($idSportMatchs);

        if (!$match || $match->getTournament()->getId() !== $idTournament) {
            return $this->json(['error' => 'Match non trouvé'], 404);
        }

        $em->remove($match);
        $em->flush();

        return $this->json(['message' => 'Match supprimé'], 200);
    }

    private function formatMatch(SportMatch $match): array
    {
        return [
            'id' => $match->getId(),
            'tournament' => $match->getTournament()->getId(),
            'player1' => $match->getPlayer1()->getId(),
            'player2' => $match->getPlayer2()->getId(),
            'matchDate' => $match->getMatchDate()->format('Y-m-d'),
            'scorePlayer1' => $match->getScorePlayer1(),
            'scorePlayer2' => $match->getScorePlayer2(),
            'status' => $match->getStatus(),
        ];
    }
}