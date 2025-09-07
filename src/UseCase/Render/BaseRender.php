<?php

namespace App\UseCase\Render;

use App\Enum\FeedName;
use App\UseCase\Render\Contract\RenderInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Twig\Environment;
use Twig\TemplateWrapper;

abstract class BaseRender implements RenderInterface
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
}