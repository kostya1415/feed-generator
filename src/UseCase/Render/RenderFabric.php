<?php

namespace App\UseCase\Render;

use App\Enum\FeedName;
use App\Repo\Contract\OffersRepoInterface;
use Exception;
use Twig\Environment;

class RenderFabric
{
    const RENDERS = [
        ExampleRender::class,
        Example2Render::class,
        // ...
    ];

    /**
     * @param FeedName $feedName
     * @param Environment $twig
     * @param OffersRepoInterface $offersRepo
     * @param string $siteUrl
     * @return BaseRender
     */
    public static function getRender(
        FeedName            $feedName,
        Environment         $twig,
        OffersRepoInterface $offersRepo,
        string              $siteUrl
    ): BaseRender
    {
        foreach (self::RENDERS as $renderClass) {
            /** @var $renderClass BaseRender */
            if ($renderClass::getFeedName() === $feedName) {
                return new $renderClass($twig, $siteUrl, $offersRepo);
            }
        }

        throw new Exception('Class not found for this feed name');
    }
}