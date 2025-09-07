<?php

namespace App\UseCase\Action;

use App\Enum\Compression;
use App\Enum\FeedName;
use App\Repo\Contract\S3RepoInterface;
use App\UseCase\Stream\Stream;
use Exception;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;
use ZipArchive;

class FeedZipAction
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
            $this->createZipCopy($io, $feedName);
        }
    }

    /**
     * @param SymfonyStyle $io
     * @param FeedName $feedName
     * @return void
     */
    private function createZipCopy(SymfonyStyle $io, FeedName $feedName)
    {
        $io->info('Deleting temporary feed ' . $feedName->value . ' if it still exists');

        $this->s3Repo->deleteTmpFeed($feedName, Compression::Zip);

        $io->info('Downloading ' . $feedName->value . ' feed...');

        $streamTmpLocal = $this->downloadFeed($feedName);

        $io->info('Success');

        try {
            $io->info('Compressing ' . $feedName->value . ' feed...');
            $streamLocalZip = $this->zipFeedTmp($feedName, $streamTmpLocal);
            $io->info('Success');
        } catch (Throwable $e) {
            $streamTmpLocal->delete();
            throw $e;
        }

        $io->info('Removing local ' . $feedName->value . ' feed...');
        $streamTmpLocal->delete();

        try {
            $io->info('Uploading compressed ' . $feedName->value . ' feed...');
            $this->uploadZipFeedTmp($feedName, $streamLocalZip);
            $io->info('Success');
        } catch (Throwable $e) {
            $streamLocalZip->delete();
            throw $e;
        }

        $io->info('Removing local zip ' . $feedName->value . ' feed..');
        $streamLocalZip->delete();
        $io->info('Success');

        $io->info('Replace old ' . $feedName->value . ' feed with new one...');
        $this->replaceFeed($feedName);
        $io->success('FeedName ' . $feedName->value . ' successfully compressed!');
    }

    /**
     * @param FeedName $feedName
     * @return Stream
     */
    private function downloadFeed(FeedName $feedName): Stream
    {
        $streamS3 = $this->s3Repo->openPublicFeedRead($feedName);

        $streamTmpLocal = Stream::tmp();

        while (!$streamS3->isEnd()) {
            $streamTmpLocal->write($streamS3->read());
        }

        $streamS3->close();

        $streamTmpLocal->resetPointer();

        return $streamTmpLocal;
    }

    /**
     * @param FeedName $feedName
     * @param Stream $streamTmpLocal
     * @return Stream
     */
    private function zipFeedTmp(FeedName $feedName, Stream $streamTmpLocal): Stream
    {
        $zipPath = '/tmp/feed-' . $feedName->value . '.yml.zip';

        if (file_exists($zipPath) && !unlink($zipPath)) {
            throw new Exception('Can\'t delete old zip file');
        }

        $zip = new ZipArchive();
        if (!$zip->open($zipPath, ZipArchive::CREATE | ZIPARCHIVE::OVERWRITE)) {
            throw new Exception('Can\'t open zip file');
        }

        if (!$zip->addFile($streamTmpLocal->getFilePath(), '/feed.yml')) {
            throw new Exception('Can\'t add zip file');
        }

        if (!$zip->close()) {
            throw new Exception('Can\'t close zip file');
        }

        return Stream::fromPath($zipPath, 'r');
    }

    /**
     * @param FeedName $feedName
     * @param Stream $streamTmpLocalZip
     * @return void
     */
    private function uploadZipFeedTmp(FeedName $feedName, Stream $streamTmpLocalZip): void
    {
        $streamS3Zip = $this->s3Repo->openTmpFeedWrite($feedName, Compression::Zip);

        while (!$streamTmpLocalZip->isEnd()) {
            $streamS3Zip->write($streamTmpLocalZip->read());
        }

        $streamS3Zip->close();
    }

    /**
     * @param FeedName $feedName
     * @return void
     */
    private function replaceFeed(FeedName $feedName): void
    {
        $this->s3Repo->deletePublicFeed($feedName, Compression::Zip);
        $this->s3Repo->publishTmpFeed($feedName, Compression::Zip);
        $this->s3Repo->deleteTmpFeed($feedName, Compression::Zip);
    }
}