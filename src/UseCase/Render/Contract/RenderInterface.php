<?php

namespace App\UseCase\Render\Contract;

use App\Enum\FeedName;
use Symfony\Component\Console\Style\SymfonyStyle;

interface RenderInterface
{
    /**
     * @return FeedName
     */
    public static function getFeedName(): FeedName;

    /**
     * @param SymfonyStyle $io
     * @return string
     */
    public function renderHeader(SymfonyStyle $io): string;

    /**
     * @param array<mixed> $category
     * @param SymfonyStyle $io
     * @return string
     */
    public function renderCategory(array $category, SymfonyStyle $io): string;

    /**
     * @param array<mixed> $offer
     * @param SymfonyStyle $io
     * @return string
     */
    public function renderOffer(array $offer, SymfonyStyle $io): string;
}