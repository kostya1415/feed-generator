<?php

namespace App\Repository;

use App\Repository\Contract\OffersRepoInterface;

class HttpOffersRepo implements OffersRepoInterface
{
    /**
     * @return array<mixed>
     */
    public function getCategories(): array
    {
        return [
            // todo
            [
                'id' => '1',
                'level' => '1',
                'url' => '/catalog/1/',
                'title' => 'first',
            ],
            [
                'id' => '2',
                'level' => '2',
                'parent_id' => '1',
                'url' => '/catalog/2/',
                'title' => 'second',
            ]
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
            [
                'id' => '1',
                'available' => 'true',
            ],
            [
                'id' => '2',
                'available' => 'false',
            ]
        ];
    }

    /**
     * @return int
     */
    public function getOffersTotal(): int
    {
        return 2; // todo
    }
}