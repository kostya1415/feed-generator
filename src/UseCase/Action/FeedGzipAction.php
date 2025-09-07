<?php

namespace App\UseCase\Action;

use App\Enum\Compression;
use App\Enum\Extension;
use App\Enum\FeedName;
use App\Repo\Contract\S3RepoInterface;
use App\UseCase\Stream\Stream;
use App\UseCase\Stream\StreamGz;
use Symfony\Component\Console\Style\SymfonyStyle;

class FeedGzipAction
{
    /**
     * @var array<FeedName>
     */
    private array $feedNames = [];

    /**
     * @param S3RepoInterface $s3Repo
     * @param array<string> $feedNamesStr
     */
    public function __construct(
        private S3RepoInterface $s3Repo,
        array                   $feedNamesStr
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
        $this->s3Repo->registerStreamWrapper();

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

        $this->downloadFromS3AsGzip($feedName);

        $io->info('Success');

        $io->info('Uploading compressed ' . $feedName->value . ' feed...');

        $this->uploadGzipToS3($feedName);

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
        $this->s3Repo->deleteTmpFeed($feedName, Extension::Yml, Compression::Gzip);

        if (file_exists($localTmpFeed = $this->getTmpGzipPath($feedName))) {
            unlink($localTmpFeed);
        }
    }

    /**
     * @param FeedName $feedName
     * @return void
     */
    private function downloadFromS3AsGzip(FeedName $feedName): void
    {
        $streamS3 = $this->s3Repo->openPublicFeedRead($feedName, Extension::Yml);
        $streamLocalGzip = StreamGz::fromPath($this->getTmpGzipPath($feedName), 'w');

        while (!$streamS3->isEnd()) {
            $streamLocalGzip->write($streamS3->read());
        }

        $streamS3->close();
        $streamLocalGzip->close();
    }

    /**
     * @param FeedName $feedName
     * @return void
     */
    private function uploadGzipToS3(FeedName $feedName): void
    {
        $streamLocalGzip = Stream::fromPath($this->getTmpGzipPath($feedName), 'r');
        $streamS3Gzip = $this->s3Repo->openTmpFeedWrite($feedName, Extension::Yml, Compression::Gzip);

        while (!$streamLocalGzip->isEnd()) {
            $streamS3Gzip->write($streamLocalGzip->read());
        }

        $streamLocalGzip->delete();
        $streamS3Gzip->close();
    }

    /**
     * @param FeedName $feedName
     * @return void
     */
    private function replaceFeed(FeedName $feedName): void
    {
        $this->s3Repo->deletePublicFeed($feedName, Extension::Yml, Compression::Gzip);
        $this->s3Repo->publishTmpFeed($feedName, Extension::Yml, Compression::Gzip);
        $this->s3Repo->deleteTmpFeed($feedName, Extension::Yml, Compression::Gzip);
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