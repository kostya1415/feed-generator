<?php

namespace App\Repo;

use App\Enum\Compression;
use App\Enum\FeedName;
use App\Repo\Contract\S3RepoInterface;
use App\UseCase\Stream\Stream;
use Aws\Result;
use Aws\S3\S3Client;

class MinioS3Repo implements S3RepoInterface
{
    /**
     * @param S3Client $client
     * @param bool $isBucketPolicySupported
     * @param string $basketName
     */
    public function __construct(
        private S3Client $client,
        private bool     $isBucketPolicySupported,
        private string   $basketName
    )
    {
    }

    /**
     * @return void
     */
    public function createBucket(): void
    {
        if (!$this->client->doesBucketExist($this->basketName)) {
            $this->client->createBucket([
                'Bucket' => $this->basketName,
            ]);

            if (!$this->isBucketPolicySupported) {
                return;
            }

            $policy = file_get_contents('storage/bucket-public-policy.json');

            $policy = sprintf($policy, $this->basketName, $this->basketName);
            $this->client->putBucketPolicy([
                'Bucket' => $this->basketName,
                'Policy' => $policy,
            ]);
        }
    }

    /**
     * @return void
     */
    public function registerStreamWrapper(): void
    {
        $this->client->registerStreamWrapper();
    }

    /**
     * @param FeedName $feedName
     * @param Compression|null $compress
     * @return Stream
     */
    public function openTmpFeedWrite(FeedName $feedName, ?Compression $compress = null): Stream
    {
        return Stream::fromPath($this->getTmpFeedPath($feedName, $compress), 'w');
    }

    /**
     * @param FeedName $feedName
     * @param Compression|null $compress
     * @return Stream
     */
    public function openPublicFeedRead(FeedName $feedName, ?Compression $compress = null): Stream
    {
        return Stream::fromPath($this->getPublicFeedPath($feedName, $compress), 'r');
    }

    /**
     * @param FeedName $feedName
     * @param Compression|null $compress
     * @return Result
     */
    public function deleteTmpFeed(FeedName $feedName, ?Compression $compress = null): Result
    {
        return $this->client->deleteObject([
            'Bucket' => $this->basketName,
            'Key' => $this->getTmpFeedKey($feedName, $compress),
        ]);
    }

    /**
     * @param FeedName $feedName
     * @param Compression|null $compress
     * @return Result
     */
    public function deletePublicFeed(FeedName $feedName, ?Compression $compress = null): Result
    {
        return $this->client->deleteObject([
            'Bucket' => $this->basketName,
            'Key' => $this->getPublicFeedKey($feedName, $compress),
        ]);
    }

    /**
     * @param FeedName $feedName
     * @param Compression|null $compress
     * @return Result
     */
    public function publishTmpFeed(FeedName $feedName, ?Compression $compress = null): Result
    {
        return $this->client->copyObject([
            'Bucket' => $this->basketName,
            'Key' => $this->getPublicFeedKey($feedName, $compress),
            'CopySource' => $this->basketName . '/' . $this->getTmpFeedKey($feedName, $compress),
        ]);
    }

    /**
     * @param FeedName $feedName
     * @param Compression|null $compress
     * @return Result
     */
    public function getPublicFeedHeaders(FeedName $feedName, ?Compression $compress = null): Result
    {
        return $this->client->headObject([
            'Bucket' => $this->basketName,
            'Key' => $this->getPublicFeedKey($feedName, $compress),
        ]);
    }

    /**
     * @param FeedName $feedName
     * @param Compression|null $compress
     * @return string
     */
    private function getTmpFeedPath(FeedName $feedName, ?Compression $compress = null): string
    {
        return $this->getS3Path() . '/' . $this->getTmpFeedKey($feedName, $compress);
    }

    /**
     * @param FeedName $feedName
     * @param Compression|null $compress
     * @return string
     */
    private function getPublicFeedPath(FeedName $feedName, ?Compression $compress = null): string
    {
        return $this->getS3Path() . '/' . $this->getPublicFeedKey($feedName, $compress);
    }


    /**
     * @param FeedName $feedName
     * @param Compression|null $compress
     * @return string
     */
    private function getTmpFeedKey(FeedName $feedName, ?Compression $compress = null): string
    {
        return 'new_feed_' . $feedName->value . '_.yml' . ($compress ? ('.' . $compress->value) : '');
    }

    /**
     * @param FeedName $feedName
     * @param Compression|null $compress
     * @return string
     */
    private function getPublicFeedKey(FeedName $feedName, ?Compression $compress = null): string
    {
        return 'feed_' . $feedName->value . '_.yml' . ($compress ? ('.' . $compress->value) : '');
    }

    /**
     * @return string
     */
    private function getS3Path(): string
    {
        return 's3://' . $this->basketName;
    }
}