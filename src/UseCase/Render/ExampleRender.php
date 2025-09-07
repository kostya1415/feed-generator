<?php

namespace App\UseCase\Render;

use App\Enum\FeedName;
use Exception;
use Symfony\Component\Console\Style\SymfonyStyle;
use Twig\Environment;
use Twig\TemplateWrapper;

class ExampleRender extends BaseRender
{
    /**
     * @return FeedName
     */
    public static function getFeedName(): FeedName
    {
        return FeedName::example;
    }

    /**
     * @param Environment $twig
     * @return TemplateWrapper
     */
    protected function getHeaderTemplate(Environment $twig): TemplateWrapper
    {
        return $twig->load('header/example.xml.twig');
    }

    /**
     * @param Environment $twig
     * @return TemplateWrapper
     */
    protected function getCategoryTemplate(Environment $twig): TemplateWrapper
    {
        return $twig->load('category/example.xml.twig');
    }

    /**
     * @param Environment $twig
     * @return TemplateWrapper
     */
    protected function getOfferTemplate(Environment $twig): TemplateWrapper
    {
        return $twig->load('offer/example.xml.twig');
    }

    /**
     * @param SymfonyStyle $io
     * @return string
     */
    public function renderHeader(SymfonyStyle $io): string
    {
        return $this->templateHeader->render([
            'date' => date('Y-m-d H:i'),
            'site_url' => $this->siteUrl,
            // todo
        ]);
    }

    /**
     * @param array<mixed> $category
     * @param SymfonyStyle $io
     * @return string
     */
    public function renderCategory(array $category, SymfonyStyle $io): string
    {
        return $this->templateCategory->render([
            'id' => $category['id'] ?? '',
            'level' => $category['level'] ?? '',
            'parent_id' => $category['parent']['id'] ?? '',
            'url' => '/catalog/' . ($category['slug'] ?? '') . '/',
            'title' => $category['title'] ?? '',
            // todo
        ]);
    }

    /**
     * @param array<mixed> $offer
     * @param SymfonyStyle $io
     * @return string
     */
    public function renderOffer(array $offer, SymfonyStyle $io): string
    {
        try {
            return $this->templateOffer->render([
                'id' => $offer['id'] ?? '',
                'available' => $offer['available'] ?? '',
                // todo
            ]);
        } catch (Exception $exception) {
            $io->error("Rendering offer #{$offer['id']} failed for 'Example' ({$exception->getMessage()}). Skipping...");
            return '';
        }
    }
}