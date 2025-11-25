<?php

namespace App\Action;

use App\Enum\Compression;
use App\Enum\FeedName;
use App\Repository\Contract\FileRepoInterface;
use App\Stream\Stream;
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
     * @throws Throwable
     */
    public function run(SymfonyStyle $io): void
    {
        $this->fileRepo->prepare();

        foreach ($this->feedNames as $feedName) {
            $this->createZipCopy($io, $feedName);
        }
    }

    /**
     * @param SymfonyStyle $io
     * @param FeedName $feedName
     * @return void
     * @throws Throwable
     */
    private function createZipCopy(SymfonyStyle $io, FeedName $feedName): void
    {
        $io->info('Deleting temporary feed ' . $feedName->value . ' if it still exists');

        $this->fileRepo->deleteTmpFeed($feedName, Compression::Zip);

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
     * @throws Exception
     */
    private function downloadFeed(FeedName $feedName): Stream
    {
        $streamRemote = $this->fileRepo->openPublicFeedRead($feedName);

        $streamTmpLocal = Stream::tmp();

        while (!$streamRemote->isEnd()) {
            $streamTmpLocal->write($streamRemote->read());
        }

        $streamRemote->close();

        $streamTmpLocal->resetPointer();

        return $streamTmpLocal;
    }

    /**
     * @param FeedName $feedName
     * @param Stream $streamTmpLocal
     * @return Stream
     * @throws Exception
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
     * @throws Exception
     */
    private function uploadZipFeedTmp(FeedName $feedName, Stream $streamTmpLocalZip): void
    {
        $streamRemoteZip = $this->fileRepo->openTmpFeedWrite($feedName, Compression::Zip);

        while (!$streamTmpLocalZip->isEnd()) {
            $streamRemoteZip->write($streamTmpLocalZip->read());
        }

        $streamRemoteZip->close();
    }

    /**
     * @param FeedName $feedName
     * @return void
     */
    private function replaceFeed(FeedName $feedName): void
    {
        $this->fileRepo->deletePublicFeed($feedName, Compression::Zip);
        $this->fileRepo->publishTmpFeed($feedName, Compression::Zip);
        $this->fileRepo->deleteTmpFeed($feedName, Compression::Zip);
    }
}