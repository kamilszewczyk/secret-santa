<?php
namespace App\Command;

use App\Entity\Member;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:draw',
    description: 'Draw and send emails.'
)]
class DrawCommand extends Command
{

    public function __construct(
        private \App\Service\MailSender $mailSender,
        private ManagerRegistry $doctrine,
        private LoggerInterface $drawLogger
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var Member[] $members */
        $members = $this->doctrine->getRepository(Member::class)->findBy(['active' => true]);
        /** @var Member[] $picked */
        $picked = [];

        foreach ($members as $member) {
            $toPick = $this->filter($members, $member->getEmail()); // Filter out current user
            foreach ($picked as $pick) { // Filter out already picked
                $toPick = $this->filter($toPick, $pick->getEmail());
            }
            if (empty($toPick)) { // Last one picking is also last one to pick.
                $member->setReceiver($members[0]->getReceiver()); // Force switch of picked with first person to avoid self-present
                $members[0]->setReceiver($member);
                break;
            }
            $random = array_rand($toPick);
            $member->setReceiver($members[$random]);
            $picked[$random] = $members[$random];
        }

        foreach ($members as $member) {
//            $output->writeln($member->getName() . ' => ' . $member->getReceiver()->getName());
            $this->drawLogger->info($member->getName() . ' => ' . $member->getReceiver()->getName());
            if ($input->getOption('dry-run') === false) {
                $this->mailSender->execute($member, $member->getReceiver()->getName());
            }
        }

        return Command::SUCCESS;
    }

    private function filter(array $members, string $email): array {
        $output = $members;
        /** @var Member $member */
        foreach ($members as $i => $member) {
            if ($member->getEmail() == $email) {
                unset($output[$i]);
            }
        }

        return $output;
    }

    // ...
    protected function configure(): void
    {
        $this
            // the command help shown when running the command with the "--help" option
            ->setHelp('This command sends test email to check design for secret santa emails')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Draw only without sending emails.')
        ;
    }
}