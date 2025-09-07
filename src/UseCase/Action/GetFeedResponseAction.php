<?php

namespace App\UseCase\Action;

use App\Enum\Compression;
use App\Enum\Extension;
use App\Enum\FeedName;
use App\Repo\Contract\S3RepoInterface;
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
     * @param S3RepoInterface $s3Repo
     * @param LoggerInterface $logger
     */
    public function __construct(
        private RequestStack    $requestStack,
        private S3RepoInterface $s3Repo,
        private LoggerInterface $logger
    )
    {
    }

    /**
     * @param FeedName $feedName
     * @param Extension $ext
     * @param Compression|null $compress
     * @return StreamedResponse|Response
     */
    public function run(FeedName $feedName, Extension $ext, ?Compression $compress = null): StreamedResponse|Response
    {
        $this->s3Repo->registerStreamWrapper();

        $response = $this->getStreamedResponse($feedName, $ext, $compress);

        if ($contentType = $this->getContentType($ext, $compress)) {
            $response->headers->set('Content-Type', $contentType);
        }

        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'feed.' . $ext->value . ($compress ? ('.' . $compress->value) : '')
        ));

        if ($etag = $this->getFeedETag($feedName, $ext)) {
            $response->headers->set('ETag', $etag);
            $response->isNotModified($this->requestStack->getCurrentRequest());
        }

        return $response;
    }

    /**
     * @param Extension $ext
     * @param Compression|null $compress
     * @return string|null
     */
    private function getContentType(Extension $ext, ?Compression $compress): ?string
    {
        $contentType = match ($compress) {
            Compression::Zip => 'application/zip',
            Compression::Gzip => 'application/gzip',
            default => null
        };

        if ($contentType) {
            return $contentType;
        }

        return match ($ext) {
            Extension::Csv => 'text/csv',
            Extension::Yml => 'application/xml',
            default => null
        };
    }

    /**
     * @param FeedName $feedName
     * @param Extension $ext
     * @param Compression|null $compress
     * @return StreamedResponse
     */
    private function getStreamedResponse(FeedName $feedName, Extension $ext, ?Compression $compress): StreamedResponse
    {
        return new StreamedResponse(function () use ($feedName, $ext, $compress) {
            try {
                $stream = $this->s3Repo->openPublicFeedRead($feedName, $ext, $compress);
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
     * @param Extension $ext
     * @return string|null
     */
    private function getFeedETag(FeedName $feedName, Extension $ext): ?string
    {
        try {
            $headers = $this->s3Repo->getPublicFeedHeaders($feedName, $ext);
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