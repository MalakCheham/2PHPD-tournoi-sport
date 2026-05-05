<?php

namespace App\EventListener;

use App\Event\ScoreUpdatedEvent;
use App\Event\TournamentWonEvent;
use App\Repository\RegistrationRepository;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: ScoreUpdatedEvent::NAME)]
#[AsEventListener(event: TournamentWonEvent::NAME)]
class SportMatchListener
{
    public function __construct(
        private MailerInterface $mailer,
        private RegistrationRepository $registrationRepo
    ) {}

    public function __invoke(object $event): void
    {
        if ($event instanceof ScoreUpdatedEvent) {
            $this->onScoreUpdated($event);
        } elseif ($event instanceof TournamentWonEvent) {
            $this->onTournamentWon($event);
        }
    }

    public function onScoreUpdated(ScoreUpdatedEvent $event): void    {
        $match = $event->getMatch();
        $updatedBy = $event->getUpdatedBy();

        // Détermine l'autre joueur
        if ($match->getPlayer1()->getEmailAddress() === $updatedBy->getEmailAddress()) {
            $otherPlayer = $match->getPlayer2();
        } else {
            $otherPlayer = $match->getPlayer1();
        }

        // Envoie une notification seulement si l'autre joueur n'a pas encore rempli son score
        $otherScore = ($otherPlayer === $match->getPlayer1()) ? $match->getScorePlayer1() : $match->getScorePlayer2();

        if ($otherScore === null) {
            $email = (new Email())
                ->from('noreply@tournoi-sport.com')
                ->to($otherPlayer->getEmailAddress())
                ->subject('Score mis à jour - À votre tour !')
                ->text(sprintf(
                    'Bonjour %s, %s a mis à jour son score. Veuillez remplir le vôtre.',
                    $otherPlayer->getFirstName(),
                    $updatedBy->getFirstName()
                ));

            $this->mailer->send($email);
        }
    }

    public function onTournamentWon(TournamentWonEvent $event): void
    {
        $tournament = $event->getTournament();
        $winner = $event->getWinner();

        // Récupère tous les participants du tournoi
        $registrations = $this->registrationRepo->findBy(['tournament' => $tournament]);

        foreach ($registrations as $registration) {
            $player = $registration->getPlayer();

            $email = (new Email())
                ->from('noreply@tournoi-sport.com')
                ->to($player->getEmailAddress())
                ->subject('Tournoi terminé - Vainqueur annoncé !')
                ->text(sprintf(
                    'Bonjour %s, le tournoi "%s" est terminé. Le vainqueur est %s %s !',
                    $player->getFirstName(),
                    $tournament->getTournamentName(),
                    $winner->getFirstName(),
                    $winner->getLastName()
                ));

            $this->mailer->send($email);
        }
    }
}