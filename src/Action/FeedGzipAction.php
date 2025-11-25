<?php

namespace App\Action;

use App\Enum\Compression;
use App\Enum\FeedName;
use App\Repository\Contract\FileRepoInterface;
use App\Stream\Stream;
use App\Stream\StreamGz;
use Exception;
use Symfony\Component\Console\Style\SymfonyStyle;

class FeedGzipAction
{
    /**
     * @var array<FeedName>
     */
    private array $feedNames = [];

    /**
     * @param FileRepoInterface $fileRepo
     * @param array<string> $feedNamesStr
     */
    public function __construct(
        private readonly FileRepoInterface $fileRepo,
        array                              $feedNamesStr
    )
    {
        foreach ($feedNamesStr as $feedNameStr) {
            $this->feedNames[] = FeedName::from($feedNameStr);
        }
    }

    /**
     * @param SymfonyStyle $io
     * @return void
     */
    public function run(SymfonyStyle $io): void
    {
        $this->fileRepo->prepare();

        foreach ($this->feedNames as $feedName) {
            $this->createGzipCopy($io, $feedName);
        }
    }

    /**
     * @param SymfonyStyle $io
     * @param FeedName $feedName
     * @return void
     */
    private function createGzipCopy(SymfonyStyle $io, FeedName $feedName): void
    {
        $io->info('Deleting temporary feed ' . $feedName->value . ' if it still exists');

        $this->deleteTmpFeeds($feedName);

        $io->info('Downloading ' . $feedName->value . ' feed as gzip...');

        $this->downloadAsGzip($feedName);

        $io->info('Success');

        $io->info('Uploading compressed ' . $feedName->value . ' feed...');

        $this->uploadGzip($feedName);

        $io->info('Success');

        $io->info('Replace old ' . $feedName->value . ' feed with new one...');

        $this->replaceFeed($feedName);

        $io->success('FeedName ' . $feedName->value . ' successfully compressed!');
    }

    /**
     * @param FeedName $feedName
     * @return void
     */
    private function deleteTmpFeeds(FeedName $feedName): void
    {
        $this->fileRepo->deleteTmpFeed($feedName, Compression::Gzip);

        if (file_exists($localTmpFeed = $this->getTmpGzipPath($feedName))) {
            unlink($localTmpFeed);
        }
    }

    /**
     * @param FeedName $feedName
     * @return void
     * @throws Exception
     */
    private function downloadAsGzip(FeedName $feedName): void
    {
        $streamRemote = $this->fileRepo->openPublicFeedRead($feedName);
        $streamLocalGzip = StreamGz::fromPath($this->getTmpGzipPath($feedName), 'w');

        while (!$streamRemote->isEnd()) {
            $streamLocalGzip->write($streamRemote->read());
        }

        $streamRemote->close();
        $streamLocalGzip->close();
    }

    /**
     * @param FeedName $feedName
     * @return void
     * @throws Exception
     */
    private function uploadGzip(FeedName $feedName): void
    {
        $streamLocalGzip = Stream::fromPath($this->getTmpGzipPath($feedName), 'r');
        $streamRemoteGzip = $this->fileRepo->openTmpFeedWrite($feedName, Compression::Gzip);

        while (!$streamLocalGzip->isEnd()) {
            $streamRemoteGzip->write($streamLocalGzip->read());
        }

        $streamLocalGzip->delete();
        $streamRemoteGzip->close();
    }

    /**
     * @param FeedName $feedName
     * @return void
     */
    private function replaceFeed(FeedName $feedName): void
    {
        $this->fileRepo->deletePublicFeed($feedName, Compression::Gzip);
        $this->fileRepo->publishTmpFeed($feedName, Compression::Gzip);
        $this->fileRepo->deleteTmpFeed($feedName, Compression::Gzip);
    }

    /**
     * @param FeedName $feedName
     * @return string
     */
    private function getTmpGzipPath(FeedName $feedName): string
    {
        return '/tmp/feed-' . $feedName->value . '.yml.gz';
    }
}