<?php

namespace App\Controller;

use App\Repository\RegistrationRepository;
use App\Repository\SportMatchRepository;
use App\Repository\TournamentRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('/users', name: 'admin_users_list', methods: ['GET'])]
    public function listUsers(UserRepository $repo): JsonResponse
    {
        $users = $repo->findAll();
        $data = [];

        foreach ($users as $user) {
            $data[] = [
                'id' => $user->getId(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'username' => $user->getUsername(),
                'emailAddress' => $user->getEmailAddress(),
                'status' => $user->getStatus(),
                'roles' => $user->getRoles(),
            ];
        }

        return $this->json($data);
    }

    #[Route('/users/{id}', name: 'admin_users_update', methods: ['PUT'])]
    public function updateUser(int $id, Request $request, UserRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $user = $repo->find($id);

        if (!$user) {
            return $this->json(['error' => 'Utilisateur non trouvé'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['status'])) $user->setStatus($data['status']);
        if (isset($data['roles'])) $user->setRoles($data['roles']);

        $em->flush();

        return $this->json(['message' => 'Utilisateur mis à jour']);
    }

    #[Route('/tournaments', name: 'admin_tournaments_list', methods: ['GET'])]
    public function listTournaments(TournamentRepository $repo): JsonResponse
    {
        $tournaments = $repo->findAll();
        $data = [];

        foreach ($tournaments as $t) {
            $data[] = [
                'id' => $t->getId(),
                'tournamentName' => $t->getTournamentName(),
                'sport' => $t->getSport(),
                'status' => $t->getStartDate() > new \DateTime() ? 'à venir' : ($t->getEndDate() < new \DateTime() ? 'terminé' : 'en cours'),
            ];
        }

        return $this->json($data);
    }

    #[Route('/registrations', name: 'admin_registrations_list', methods: ['GET'])]
    public function listRegistrations(RegistrationRepository $repo): JsonResponse
    {
        $registrations = $repo->findAll();
        $data = [];

        foreach ($registrations as $r) {
            $data[] = [
                'id' => $r->getId(),
                'player' => $r->getPlayer()->getId(),
                'tournament' => $r->getTournament()->getId(),
                'status' => $r->getStatus(),
                'registrationDate' => $r->getRegistrationDate()->format('Y-m-d'),
            ];
        }

        return $this->json($data);
    }

    #[Route('/registrations/{id}', name: 'admin_registrations_update', methods: ['PUT'])]
    public function updateRegistration(int $id, Request $request, RegistrationRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $registration = $repo->find($id);

        if (!$registration) {
            return $this->json(['error' => 'Inscription non trouvée'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['status'])) $registration->setStatus($data['status']);

        $em->flush();

        return $this->json(['message' => 'Inscription mise à jour']);
    }

    #[Route('/sport-matchs', name: 'admin_matchs_list', methods: ['GET'])]
    public function listMatchs(SportMatchRepository $repo): JsonResponse
    {
        $matchs = $repo->findAll();
        $data = [];

        foreach ($matchs as $m) {
            $data[] = [
                'id' => $m->getId(),
                'tournament' => $m->getTournament()->getId(),
                'player1' => $m->getPlayer1()->getId(),
                'player2' => $m->getPlayer2()->getId(),
                'scorePlayer1' => $m->getScorePlayer1(),
                'scorePlayer2' => $m->getScorePlayer2(),
                'status' => $m->getStatus(),
            ];
        }

        return $this->json($data);
    }

    #[Route('/sport-matchs/{id}', name: 'admin_matchs_update', methods: ['PUT'])]
    public function updateMatch(int $id, Request $request, SportMatchRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $match = $repo->find($id);

        if (!$match) {
            return $this->json(['error' => 'Match non trouvé'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['scorePlayer1'])) $match->setScorePlayer1($data['scorePlayer1']);
        if (isset($data['scorePlayer2'])) $match->setScorePlayer2($data['scorePlayer2']);
        if (isset($data['status'])) $match->setStatus($data['status']);

        if ($match->getScorePlayer1() !== null && $match->getScorePlayer2() !== null) {
            $match->setStatus('terminé');
        }

        $em->flush();

        return $this->json(['message' => 'Match mis à jour']);
    }
}