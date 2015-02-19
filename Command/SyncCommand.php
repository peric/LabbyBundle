<?php

namespace Velikonja\LabbyBundle\Command;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Stopwatch\Stopwatch;

class SyncCommand extends BaseCommand
{
    const COMMAND_NAME = 'labby:sync';

    /**
     * Configure command.
     */
    protected function configure()
    {
        $this
            ->setName(self::COMMAND_NAME)
            ->setDescription('Run synchronization of filesystem maps and DB.')
            ->setRoles(array(self::ROLE_LOCAL));
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @throws \Exception
     *
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $syncer    = $this->getContainer()->get('velikonja_labby.service.syncer');
        $stopwatch = new Stopwatch();

        $stopwatch->start('sync');
        $syncer->syncDb($output);
        $syncer->syncFs($output);
        $event = $stopwatch->stop('sync');

        $output->writeln('');
        $output->writeln(
            sprintf(
                '<info>Finished in %.2f seconds!</info>',
                $event->getDuration() / 1000
            )
        );

        $this->executeAfter($output);
    }

    /**
     * @param OutputInterface $output
     *
     * Executes commands that are specified to run after the sync process
     */
    private function executeAfter(OutputInterface $output)
    {
        $app = $this->getApplication();

        $afterCommands = $this->getContainer()->getParameter('velikonja_labby.config.after');

        foreach ($afterCommands as $afterCommand) {
            $output->writeln(sprintf("<info>Executing $afterCommand</info>"));
            $in = new ArrayInput(array('command' => $afterCommand, '--env' => 'dev'));
            $app->doRun($in, $output);
        }

        $output->writeln('After commands successfully executed.');
    }
}
