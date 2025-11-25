<?php

namespace App\Repository\Contract;

interface OffersRepoInterface
{
    /**
     * @return array<mixed>
     */
    public function getCategories(): array;

    /**
     * @param int $limit
     * @param int $page
     * @return array<mixed>
     */
    public function getOffers(int $limit = 50, int $page = 1): array;

    /**
     * @return int
     */
    public function getOffersTotal(): int;
}