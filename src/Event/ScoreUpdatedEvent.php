<?php

namespace App\Event;

use App\Entity\SportMatch;
use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

class ScoreUpdatedEvent extends Event
{
    public const NAME = 'score.updated';

    public function __construct(
        private SportMatch $match,
        private User $updatedBy
    ) {}

    public function getMatch(): SportMatch
    {
        return $this->match;
    }

    public function getUpdatedBy(): User
    {
        return $this->updatedBy;
    }
}