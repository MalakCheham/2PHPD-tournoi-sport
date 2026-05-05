<?php

namespace App\Tests;

use App\Entity\Tournament;
use PHPUnit\Framework\TestCase;

class TournamentTest extends TestCase
{
    public function testTournamentStatus(): void
    {
        $tournament = new Tournament();
        $tournament->setTournamentName('Test Tournament');
        $tournament->setDescription('Test');
        $tournament->setStartDate(new \DateTime('+1 day'));
        $tournament->setEndDate(new \DateTime('+10 days'));

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

        $this->assertEquals('à venir', $status);
    }

    public function testTournamentStatusEnCours(): void
    {
        $tournament = new Tournament();
        $tournament->setStartDate(new \DateTime('-1 day'));
        $tournament->setEndDate(new \DateTime('+10 days'));

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

        $this->assertEquals('en cours', $status);
    }

    public function testTournamentStatusTermine(): void
    {
        $tournament = new Tournament();
        $tournament->setStartDate(new \DateTime('-10 days'));
        $tournament->setEndDate(new \DateTime('-1 day'));

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

        $this->assertEquals('terminé', $status);
    }

    public function testTournamentName(): void
    {
        $tournament = new Tournament();
        $tournament->setTournamentName('Super Tournoi');

        $this->assertEquals('Super Tournoi', $tournament->getTournamentName());
    }

    public function testTournamentMaxParticipants(): void
    {
        $tournament = new Tournament();
        $tournament->setMaxParticipants(16);

        $this->assertEquals(16, $tournament->getMaxParticipants());
    }
}