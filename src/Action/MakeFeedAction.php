<?php

namespace App\Action;

use App\DTO\FeedRenderData;
use App\Enum\FeedName;
use App\Render\Contract\RenderInterface;
use App\Render\RenderFabric;
use App\Repository\Contract\OffersRepoInterface;
use App\Repository\Contract\FileRepoInterface;
use Exception;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;
use Twig\Environment;

class MakeFeedAction
{
    /**
     * @var array<FeedRenderData>
     */
    private array $feedsData = [];

    /**
     * @param OffersRepoInterface $offersRepo
     * @param Environment $twig
     * @param FeedZipAction $feedZip
     * @param FeedGzipAction $feedGzip
     * @param FileRepoInterface $fileRepo
     * @param int $offerLimit
     * @param string $siteUrl
     */
    public function __construct(
        private readonly OffersRepoInterface $offersRepo,
        private readonly Environment         $twig,
        private readonly FeedZipAction       $feedZip,
        private readonly FeedGzipAction      $feedGzip,
        private readonly FileRepoInterface   $fileRepo,
        private readonly int                 $offerLimit,
        private readonly string              $siteUrl,
    )
    {
    }

    /**
     * @param SymfonyStyle $io
     * @return void
     * @throws Exception
     */
    public function run(SymfonyStyle $io): void
    {
        try {
            $io->info('Prepare file storage...');

            $this->fileRepo->prepare();

            $io->info('Successfully. Now let’s try to delete temporary feeds, if they exists...');

            $this->deleteTmpFeeds();

            $io->info('Successfully. Now let’s open temporary feeds streams...');

            foreach (FeedName::cases() as $feedName) {
                $render = RenderFabric::getRender($feedName, $this->twig, $this->offersRepo, $this->siteUrl);

                $this->addFeedData($render);
            }

            $io->info('Successfully. Now let’s insert headers into the text of the new feeds...');

            $this->uploadHeaders($io);

            $io->info('Successfully. Now let’s insert list of categories into the text of the new feeds...');

            $this->uploadCategories($io);

            $io->info('Successfully. Now let’s insert list of offers into the text of the new feeds. It may take a long time...');

            $this->uploadOffers($io);

            $io->info('Successfully. Now let’s insert footer into the text of the new feeds...');

            $this->uploadFooter();

            $io->info('Successfully. Now let’s try to close feeds streams...');

            $this->closeStreams();
        } catch (Throwable $e) {
            $io->error('Error creating feed: ' . $e->getMessage());
            $this->closeStreams();
            return;
        }

        try {
            $io->info('Successfully. Now let’s try to replace old feeds with new ones...');

            $this->replaceFeeds();
        } catch (Throwable $e) {
            $io->error('Error replacing feed: ' . $e->getMessage());
            return;
        }

        try {
            $io->info('Successfully. Now let’s try to compress some feeds to zip...');
            $this->feedZip->run($io);
        } catch (Throwable $e) {
            $io->error('Error compressing feed: ' . $e->getMessage());
        }

        try {
            $io->info('Successfully. Now let’s try to compress some feeds to gzip...');
            $this->feedGzip->run($io);
        } catch (Throwable $e) {
            $io->error('Error compressing feed: ' . $e->getMessage());
        }

        $io->info('Successfully. Peak of memory usage: ' . memory_get_peak_usage() . ' bytes');
    }

    /**
     * @param RenderInterface $render
     * @return void
     */
    private function addFeedData(RenderInterface $render): void
    {
        $this->feedsData[] = new FeedRenderData(
            stream: $this->fileRepo->openTmpFeedWrite($render::getFeedName()),
            render: $render
        );
    }

    /**
     * @return array<FeedRenderData>
     * @throws Exception
     */
    private function getFeedData(): array
    {
        if (!$this->feedsData) {
            throw new Exception('feedData variable is not set');
        }

        return $this->feedsData;
    }

    /**
     * @return void
     */
    private function deleteTmpFeeds(): void
    {
        foreach (FeedName::cases() as $feedName) {
            $this->fileRepo->deleteTmpFeed($feedName);
        }
    }

    /**
     * @param SymfonyStyle $io
     * @return void
     * @throws Exception
     */
    private function uploadHeaders(SymfonyStyle $io): void
    {
        foreach ($this->getFeedData() as $feedData) {
            $feedData->getStream()->write($feedData->getRender()->renderHeader($io));
        }
    }

    /**
     * @param SymfonyStyle $io
     * @return void
     * @throws Exception
     */
    private function uploadCategories(SymfonyStyle $io): void
    {
        foreach ($this->getFeedData() as $feedData) {
            $feedData->getStream()->write('
            <categories>
            ');
        }

        $categories = $this->offersRepo->getCategories();

        if (!$categories) {
            throw new Exception('Failed to get categories');
        }

        foreach ($categories as $category) {
            if (!is_array($category)) {
                continue;
            }

            foreach ($this->getFeedData() as $feedData) {
                $feedData->getStream()->write($feedData->getRender()->renderCategory($category, $io));
            }
        }

        foreach ($this->getFeedData() as $feedData) {
            $feedData->getStream()->write('
            </categories>
            ');
        }
    }

    /**
     * @param SymfonyStyle $io
     * @return void
     * @throws Exception
     */
    private function uploadOffers(SymfonyStyle $io): void
    {
        foreach ($this->getFeedData() as $feedData) {
            $feedData->getStream()->write('
            <offers>
            ');
        }

        $total = $this->offersRepo->getOffersTotal();

        if (!$total) {
            throw new Exception('Failed to enquire the total number of offers');
        }

        $numberOfPages = ceil($total / $this->offerLimit);

        foreach ($io->progressIterate(range(1, $numberOfPages)) as $page) {
            try {
                $offers = $this->offersRepo->getOffers(
                    limit: $this->offerLimit,
                    page: (int)$page,
                );

                foreach ($offers as $offer) {
                    foreach ($this->getFeedData() as $feedData) {
                        $feedData->getStream()->write($feedData->getRender()->renderOffer($offer, $io));
                    }
                }
            } catch (Throwable $e) {
                $io->error('Error while exporting offers page ' . $page . ': ' . $e->getMessage());
            }
        }

        foreach ($this->getFeedData() as $feedData) {
            $feedData->getStream()->write('
            </offers>
            ');
        }
    }

    /**
     * @return void
     * @throws Exception
     */
    private function uploadFooter(): void
    {
        $footerYml = $this->twig->load('footer.xml.twig')->render();

        foreach ($this->getFeedData() as $feedData) {
            $feedData->getStream()->write($footerYml);
        }
    }

    /**
     * @return void
     * @throws Exception
     */
    private function closeStreams(): void
    {
        foreach ($this->getFeedData() as $feedData) {
            $feedData->getStream()->close();
        }
    }

    /**
     * @return void
     * @throws Exception
     */
    private function replaceFeeds(): void
    {
        foreach ($this->getFeedData() as $feedData) {
            $this->fileRepo->deletePublicFeed($feedData->getRender()::getFeedName());
            $this->fileRepo->publishTmpFeed($feedData->getRender()::getFeedName());
            $this->fileRepo->deleteTmpFeed($feedData->getRender()::getFeedName());
        }
    }
}