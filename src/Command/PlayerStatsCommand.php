<?php

namespace App\Command;

use App\Repository\SportMatchRepository;
use App\Repository\TournamentRepository;
use App\Repository\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:player-stats',
    description: 'Affiche les victoires et défaites d\'un joueur',
)]
class PlayerStatsCommand extends Command
{
    public function __construct(
        private UserRepository $userRepo,
        private SportMatchRepository $matchRepo,
        private TournamentRepository $tournamentRepo
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('userId', InputArgument::REQUIRED, 'ID du joueur')
            ->addArgument('tournamentId', InputArgument::OPTIONAL, 'ID du tournoi (optionnel)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $userId = $input->getArgument('userId');
        $tournamentId = $input->getArgument('tournamentId');

        $user = $this->userRepo->find($userId);

        if (!$user) {
            $output->writeln('<error>Joueur non trouvé</error>');
            return Command::FAILURE;
        }

        $output->writeln(sprintf('Stats pour %s %s :', $user->getFirstName(), $user->getLastName()));

        // Récupère tous les matchs terminés du joueur
        $matches = $this->matchRepo->findBy(['status' => 'terminé']);

        if ($tournamentId) {
            $tournament = $this->tournamentRepo->find($tournamentId);
            if (!$tournament) {
                $output->writeln('<error>Tournoi non trouvé</error>');
                return Command::FAILURE;
            }
            $matches = $this->matchRepo->findBy(['status' => 'terminé', 'tournament' => $tournament]);
            $output->writeln(sprintf('Tournoi : %s', $tournament->getTournamentName()));
        }

        $wins = 0;
        $losses = 0;

        foreach ($matches as $match) {
            $isPlayer1 = $match->getPlayer1()->getId() === $user->getId();
            $isPlayer2 = $match->getPlayer2()->getId() === $user->getId();

            if (!$isPlayer1 && !$isPlayer2) {
                continue;
            }

            $score1 = $match->getScorePlayer1();
            $score2 = $match->getScorePlayer2();

            if ($isPlayer1) {
                if ($score1 > $score2) $wins++;
                else $losses++;
            } else {
                if ($score2 > $score1) $wins++;
                else $losses++;
            }
        }

        $output->writeln(sprintf('Victoires : %d', $wins));
        $output->writeln(sprintf('Défaites : %d', $losses));

        return Command::SUCCESS;
    }
}