<?php

namespace App\Command;

use App\Repository\UserActionsRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:purge-logs',
    description: 'Supprime les logs d\'actions utilisateur de plus de 12 mois (conformité RGPD)',
)]
class PurgeLogsCommand extends Command
{
    public function __construct(private UserActionsRepository $userActionsRepository)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $before = new \DateTimeImmutable('-12 months');
        $deleted = $this->userActionsRepository->deleteOlderThan($before);

        $output->writeln(sprintf('%d entrée(s) supprimée(s) (antérieures au %s)', $deleted, $before->format('d/m/Y')));

        return Command::SUCCESS;
    }
}
