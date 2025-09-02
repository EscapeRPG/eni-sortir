<?php

namespace App\MessageHandler;

use App\Message\SendMailReminder;
use App\Repository\SortieRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class SendMailReminderHandler

{
    private SortieRepository $sortieRepository;
    private MailerInterface $mailer;

    public function __construct(SortieRepository $sortieRepository, MailerInterface $mailer)
    {
        $this->sortieRepository = $sortieRepository;
        $this->mailer = $mailer;

    }

    public function __invoke(SendMailReminder $message)
    {
        $event = $this->sortieRepository->find($message->getEventId());
        if(!$event) {
            return;
        }//si on à pas d'event on stop


        foreach ($event->getParticipants() as $user) {
            $email =(new TemplatedEmail())
                ->from('no-reply@eni-sortir.fr')
                ->to($user->getEmail())
                ->subject("Rappel : {$event->getName()} dans 48h")
                ->htmlTemplate('email/reminder.html.twig')
                ->context([
                    'event' => $event,
                    'user' => $user,
                ]);
            $this->mailer->send($email);
        }

    }
}
