<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/players')]
class PlayerController extends AbstractController
{
    #[Route('', name: 'players_list', methods: ['GET'])]
    public function list(UserRepository $repo): JsonResponse
    {
        $players = $repo->findAll();
        $data = [];

        foreach ($players as $player) {
            $data[] = $this->formatPlayer($player);
        }

        return $this->json($data);
    }

    #[Route('/{id}', name: 'players_show', methods: ['GET'])]
    public function show(User $user): JsonResponse
    {
        return $this->json($this->formatPlayer($user));
    }

    #[Route('/{id}', name: 'players_update', methods: ['PUT'])]
    public function update(User $user, Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (isset($data['firstName'])) $user->setFirstName($data['firstName']);
        if (isset($data['lastName'])) $user->setLastName($data['lastName']);
        if (isset($data['username'])) $user->setUsername($data['username']);
        if (isset($data['emailAddress'])) $user->setEmailAddress($data['emailAddress']);
        if (isset($data['status'])) $user->setStatus($data['status']);
        if (isset($data['password'])) {
            $user->setPassword($passwordHasher->hashPassword($user, $data['password']));
        }

        $em->flush();

        return $this->json($this->formatPlayer($user));
    }

    #[Route('/{id}', name: 'players_delete', methods: ['DELETE'])]
    public function delete(User $user, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($user);
        $em->flush();

        return $this->json(['message' => 'Joueur supprimé'], 200);
    }

    private function formatPlayer(User $user): array
    {
        return [
            'id' => $user->getId(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'username' => $user->getUsername(),
            'emailAddress' => $user->getEmailAddress(),
            'status' => $user->getStatus(),
            'roles' => $user->getRoles(),
        ];
    }
}