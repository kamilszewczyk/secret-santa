<?php
// src/Command/CreateUserCommand.php
namespace App\Command;

use App\Entity\Member;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Uid\Uuid;

#[AsCommand(
    name: 'app:test-email',
    description: 'Sends test email.'
)]
class TestEmailCommand extends Command
{

    public function __construct(
        private \App\Service\MailSender $mailSender
    ) {


        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $member = new Member();
        $member->setEmail('kamil@szewczyk.org');
        $member->setName('Kamil');
        $member->setUuid(Uuid::v4());
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