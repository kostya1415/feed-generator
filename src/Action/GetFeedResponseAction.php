<?php

namespace App\Action;

use App\Enum\Compression;
use App\Enum\FeedName;
use App\Repository\Contract\FileRepoInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class GetFeedResponseAction
{
    /**
     * @param RequestStack $requestStack
     * @param FileRepoInterface $fileRepo
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly RequestStack      $requestStack,
        private readonly FileRepoInterface $fileRepo,
        private readonly LoggerInterface   $logger
    )
    {
    }

    /**
     * @param FeedName $feedName
     * @param Compression|null $compress
     * @return StreamedResponse|Response
     */
    public function run(FeedName $feedName, ?Compression $compress = null): StreamedResponse|Response
    {
        $this->fileRepo->prepare();

        $response = $this->getStreamedResponse($feedName, $compress);

        if ($contentType = $this->getContentType($compress)) {
            $response->headers->set('Content-Type', $contentType);
        }

        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'feed.yml' . ($compress ? ('.' . $compress->value) : '')
        ));

        if ($etag = $this->getFeedETag($feedName)) {
            $response->headers->set('ETag', $etag);
            $response->isNotModified($this->requestStack->getCurrentRequest());
        }

        return $response;
    }

    /**
     * @param Compression|null $compress
     * @return string
     */
    private function getContentType(?Compression $compress): string
    {
        return match ($compress) {
            Compression::Zip => 'application/zip',
            Compression::Gzip => 'application/gzip',
            default => 'application/xml'
        };
    }

    /**
     * @param FeedName $feedName
     * @param Compression|null $compress
     * @return StreamedResponse
     */
    private function getStreamedResponse(FeedName $feedName, ?Compression $compress): StreamedResponse
    {
        return new StreamedResponse(function () use ($feedName, $compress) {
            try {
                $stream = $this->fileRepo->openPublicFeedRead($feedName, $compress);
            } catch (Throwable $e) {
                $this->logger->error('Download feed exception: ' . $e->getMessage());
                throw new NotFoundHttpException('FeedName not found');
            }

            try {
                while (!$stream->isEnd()) {
                    echo $stream->read();
                }
            } catch (Throwable $e) {
                $this->logger->error('Reed downloaded feed exception: ' . $e->getMessage());
            }

            $stream->close();
        });
    }

    /**
     * @param FeedName $feedName
     * @return string|null
     */
    private function getFeedETag(FeedName $feedName): ?string
    {
        try {
            $headers = $this->fileRepo->getPublicFeedHeaders($feedName);
        } catch (Throwable $e) {
            $this->logger->error('Can\'t get ' . $feedName->value . ' feed headers: ' . $e->getMessage());
            return null;
        }

        $headers = $headers->toArray();
        foreach ($headers as $key => $value) {
            if ($key === 'ETag' && is_string($value ?: null)) {
                return $value;
            }
        }

        $this->logger->error('ETag not found in ' . $feedName->value . ': ' . json_encode($headers));

        return null;
    }
}