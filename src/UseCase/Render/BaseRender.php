<?php

namespace App\UseCase\Render;

use App\Enum\FeedName;
use Symfony\Component\Console\Style\SymfonyStyle;
use Twig\Environment;
use Twig\TemplateWrapper;

abstract class BaseRender
{
    /**
     * @var TemplateWrapper
     */
    protected TemplateWrapper $templateHeader;

    /**
     * @var TemplateWrapper
     */
    protected TemplateWrapper $templateCategory;

    /**
     * @var TemplateWrapper
     */
    protected TemplateWrapper $templateOffer;

    /**
     * @param Environment $twig
     * @param string $siteUrl
     */
    public function __construct(Environment $twig, protected string $siteUrl)
    {
        $this->templateHeader = $this->getHeaderTemplate($twig);
        $this->templateCategory = $this->getCategoryTemplate($twig);
        $this->templateOffer = $this->getOfferTemplate($twig);
    }

    /**
     * @return FeedName
     */
    public abstract static function getFeedName(): FeedName;

    /**
     * @param Environment $twig
     * @return TemplateWrapper
     */
    protected abstract function getHeaderTemplate(Environment $twig): TemplateWrapper;

    /**
     * @param Environment $twig
     * @return TemplateWrapper
     */
    protected abstract function getCategoryTemplate(Environment $twig): TemplateWrapper;

    /**
     * @param Environment $twig
     * @return TemplateWrapper
     */
    protected abstract function getOfferTemplate(Environment $twig): TemplateWrapper;

    /**
     * @param SymfonyStyle $io
     * @return string
     */
    public abstract function renderHeader(SymfonyStyle $io): string;

    /**
     * @param array<mixed> $category
     * @param SymfonyStyle $io
     * @return string
     */
    public abstract function renderCategory(array $category, SymfonyStyle $io): string;

    /**
     * @param array<mixed> $offer
     * @param SymfonyStyle $io
     * @return string
     */
    public abstract function renderOffer(array $offer, SymfonyStyle $io): string;
}