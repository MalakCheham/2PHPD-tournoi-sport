<?php

namespace App\Command;

use App\Entity\Registration;
use App\Entity\SportMatch;
use App\Entity\Tournament;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:load-fixtures',
    description: 'Charge les données de test en base de données',
)]
class LoadFixturesCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Chargement des fixtures...');

        // Création des utilisateurs
        $admin = new User();
        $admin->setFirstName('Admin');
        $admin->setLastName('System');
        $admin->setUsername('admin');
        $admin->setEmailAddress('admin@tournoi.com');
        $admin->setStatus('actif');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin123'));
        $this->em->persist($admin);

        $user1 = new User();
        $user1->setFirstName('Alice');
        $user1->setLastName('Dupont');
        $user1->setUsername('alice');
        $user1->setEmailAddress('alice@tournoi.com');
        $user1->setStatus('actif');
        $user1->setPassword($this->passwordHasher->hashPassword($user1, 'alice123'));
        $this->em->persist($user1);

        $user2 = new User();
        $user2->setFirstName('Bob');
        $user2->setLastName('Martin');
        $user2->setUsername('bob');
        $user2->setEmailAddress('bob@tournoi.com');
        $user2->setStatus('actif');
        $user2->setPassword($this->passwordHasher->hashPassword($user2, 'bob123'));
        $this->em->persist($user2);

        // Création d'un tournoi
        $tournament = new Tournament();
        $tournament->setTournamentName('Tournoi de Tennis 2026');
        $tournament->setStartDate(new \DateTime('2026-05-01'));
        $tournament->setEndDate(new \DateTime('2026-05-30'));
        $tournament->setLocation('Paris');
        $tournament->setDescription('Un super tournoi de tennis');
        $tournament->setMaxParticipants(16);
        $tournament->setSport('Tennis');
        $tournament->setOrganizer($admin);
        $this->em->persist($tournament);

        // Inscriptions confirmées
        $reg1 = new Registration();
        $reg1->setPlayer($user1);
        $reg1->setTournament($tournament);
        $reg1->setRegistrationDate(new \DateTime());
        $reg1->setStatus('confirmée');
        $this->em->persist($reg1);

        $reg2 = new Registration();
        $reg2->setPlayer($user2);
        $reg2->setTournament($tournament);
        $reg2->setRegistrationDate(new \DateTime());
        $reg2->setStatus('confirmée');
        $this->em->persist($reg2);

        // Création d'un match
        $match = new SportMatch();
        $match->setTournament($tournament);
        $match->setPlayer1($user1);
        $match->setPlayer2($user2);
        $match->setMatchDate(new \DateTime('2026-05-15'));
        $match->setStatus('en attente');
        $this->em->persist($match);

        $this->em->flush();

        $output->writeln('Fixtures chargées avec succès !');
        $output->writeln('Utilisateurs créés :');
        $output->writeln('  - admin@tournoi.com / admin123 (ROLE_ADMIN)');
        $output->writeln('  - alice@tournoi.com / alice123');
        $output->writeln('  - bob@tournoi.com / bob123');

        return Command::SUCCESS;
    }
}