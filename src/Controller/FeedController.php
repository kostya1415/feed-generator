<?php

namespace App\Controller;

use App\Enum\Compression;
use App\Enum\FeedName;
use App\UseCase\Action\GetFeedResponseAction;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;

class FeedController extends AbstractController
{
    /**
     * @param GetFeedResponseAction $getFeedResponse
     */
    public function __construct(private GetFeedResponseAction $getFeedResponse)
    {
    }

    #[Route(path: '/example/feed.yml', name: 'feedExample', methods: 'GET')]
    public function example(): StreamedResponse|Response
    {
        return $this->getFeedResponse->run(feedName: FeedName::example);
    }

    #[Route(path: '/example/feed.yml.gz', name: 'feedExampleGzip', methods: 'GET')]
    public function exampleGzip(): StreamedResponse|Response
    {
        return $this->getFeedResponse->run(feedName: FeedName::example, compress: Compression::Gzip);
    }

    #[Route(path: '/example2/feed.yml', name: 'feedExample2', methods: 'GET')]
    public function example2(): StreamedResponse|Response
    {
        return $this->getFeedResponse->run(feedName: FeedName::example2);
    }

    #[Route(path: '/example2/feed.yml.zip', name: 'feedExample2Zip', methods: 'GET')]
    public function example2Zip(): StreamedResponse|Response
    {
        return $this->getFeedResponse->run(feedName: FeedName::example2, compress: Compression::Zip);
    }
}