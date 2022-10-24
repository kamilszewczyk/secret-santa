<?php
// src/Command/CreateUserCommand.php
namespace App\Command;

use App\Entity\Member;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:test-email',
    description: 'Sends test email.'
)]
class TestEmailCommand extends Command
{

    public function __construct(
        private \App\Service\MailSender $mailSender,
        private Member $member
    ) {


        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $member = $this->member;
        $member->setEmail('kamil@szewczyk.org');
        $member->setName('Kamil');
        $this->mailSender->execute($member, 'Gosia');

        return Command::SUCCESS;
    }

    // ...
    protected function configure(): void
    {
        $this
            // the command help shown when running the command with the "--help" option
            ->setHelp('This command sends test email to check design for secret santa emails')
        ;
    }
}