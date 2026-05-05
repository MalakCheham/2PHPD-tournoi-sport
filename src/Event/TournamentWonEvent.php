<?php

namespace App\Event;

use App\Entity\Tournament;
use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

class TournamentWonEvent extends Event
{
    public const NAME = 'tournament.won';

    public function __construct(
        private Tournament $tournament,
        private User $winner
    ) {}

    public function getTournament(): Tournament
    {
        return $this->tournament;
    }

    public function getWinner(): User
    {
        return $this->winner;
    }
}