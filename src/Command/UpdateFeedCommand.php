<?php

namespace App\Command;

use App\Action\MakeFeedAction;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Lock\LockFactory;
use Throwable;

#[AsCommand(
    name: 'app:update-feed',
    description: 'Update the whole feed file',
)]
class UpdateFeedCommand extends Command
{
    /**
     * @param LockFactory $lockFactory
     * @param MakeFeedAction $makeFeed
     * @param string|null $name
     */
    public function __construct(
        private readonly LockFactory    $lockFactory,
        private readonly MakeFeedAction $makeFeed,
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

        if (!$lock->acquire()) {
            $io->caution('Command ' . $this->getName() . ' is already running');

            return Command::SUCCESS;
        }

        try {
            $this->makeFeed->run($io);
        } catch (Throwable $e) {
            $lock->release();
            $io->error('Update feeds error: ' . $e->getMessage());

            return Command::FAILURE;
        }

        $lock->release();

        $io->info('Command ' . $this->getName() . ' successfully finished!');

        return Command::SUCCESS;
    }
}
