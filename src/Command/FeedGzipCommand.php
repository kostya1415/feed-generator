<?php

namespace App\Command;

use App\Action\FeedGzipAction;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Lock\LockFactory;
use Throwable;

#[AsCommand(
    name: 'app:feed-gzip',
    description: 'Creates a gzip feeds copy',
)]
class FeedGzipCommand extends Command
{
    /**
     * @param LockFactory $lockFactory
     * @param FeedGzipAction $feedGzip
     * @param string|null $name
     */
    public function __construct(
        private readonly LockFactory    $lockFactory,
        private readonly FeedGzipAction $feedGzip,
        string                          $name = null
    )
    {
        parent::__construct($name);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->info('Command ' . $this->getName() . ' started');

        $lock = $this->lockFactory->createLock(__METHOD__, 3600);

        if (!$lock->acquire(false)) {
            $io->caution('Command ' . $this->getName() . ' is already running');

            return Command::SUCCESS;
        }

        try {
            $this->feedGzip->run($io);
        } catch (Throwable $e) {
            $io->error('Feeds compression error: ' . $e->getMessage());
            $lock->release();
            return Command::FAILURE;
        }

        $lock->release();

        $io->info('Command ' . $this->getName() . ' successfully finished!');

        return Command::SUCCESS;
    }
}