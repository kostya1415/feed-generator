<?php

namespace App\Repo;

use App\Repo\Contract\OffersRepoInterface;

class HttpOffersRepo implements OffersRepoInterface
{
    /**
     * @return array<mixed>
     */
    public function getCategories(): array
    {
        return [
            // todo
        ];
    }

    /**
     * @param int $limit
     * @param int $page
     * @return array<mixed>
     */
    public function getOffers(int $limit = 50, int $page = 1): array
    {
        return [
            // todo
        ];
    }

    /**
     * @return int
     */
    public function getOffersTotal(): int
    {
        return 0; // todo
    }
}