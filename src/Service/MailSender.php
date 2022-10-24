<?php

namespace App\Service;

use App\Entity\Member;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class MailSender
{
    public function __construct(
        private readonly int             $priceLimit,
        private readonly MailerInterface $mailer
    ) {

    }

    public function execute(Member $member, string $recipient) {
        $email = (new TemplatedEmail())
            ->from(new Address('kamil@szewczyk.org', 'Święty Mikołaj'))
            ->to($member->getEmail())
            ->subject('Wyniki losowania ' . date('Y'))
            ->htmlTemplate('emails/draw.html.twig')
            ->textTemplate('emails/draw.txt.twig')
            ->context([
                          'member' => $member->getName(),
                          'receiver' => $recipient,
                          'price_limit' => $this->priceLimit
                      ]);

        $this->mailer->send($email);
    }
}