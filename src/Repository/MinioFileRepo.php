<?php

namespace App\Repository;

use App\Enum\Compression;
use App\Enum\FeedName;
use App\Repository\Contract\FileRepoInterface;
use App\Stream\Stream;
use Aws\S3\S3Client;
use Exception;

class MinioFileRepo implements FileRepoInterface
{
    /**
     * @param S3Client $client
     * @param bool $isBucketPolicySupported
     * @param string $basketName
     */
    public function __construct(
        private readonly S3Client $client,
        private readonly bool     $isBucketPolicySupported,
        private readonly string   $basketName
    )
    {
    }

    /**
     * @return void
     */
    public function prepare(): void
    {
        $this->registerStreamWrapper();
        $this->createBucket();
    }

    /**
     * @return void
     */
    private function createBucket(): void
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
    private function registerStreamWrapper(): void
    {
        $this->client->registerStreamWrapper();
    }

    /**
     * @param FeedName $feedName
     * @param Compression|null $compress
     * @return Stream
     * @throws Exception
     */
    public function openTmpFeedWrite(FeedName $feedName, ?Compression $compress = null): Stream
    {
        return Stream::fromPath($this->getTmpFeedPath($feedName, $compress), 'w');
    }

    /**
     * @param FeedName $feedName
     * @param Compression|null $compress
     * @return Stream
     * @throws Exception
     */
    public function openPublicFeedRead(FeedName $feedName, ?Compression $compress = null): Stream
    {
        return Stream::fromPath($this->getPublicFeedPath($feedName, $compress), 'r');
    }

    /**
     * @param FeedName $feedName
     * @param Compression|null $compress
     * @return void
     */
    public function deleteTmpFeed(FeedName $feedName, ?Compression $compress = null): void
    {
        $this->client->deleteObject([
            'Bucket' => $this->basketName,
            'Key' => $this->getTmpFeedKey($feedName, $compress),
        ]);
    }

    /**
     * @param FeedName $feedName
     * @param Compression|null $compress
     * @return void
     */
    public function deletePublicFeed(FeedName $feedName, ?Compression $compress = null): void
    {
        $this->client->deleteObject([
            'Bucket' => $this->basketName,
            'Key' => $this->getPublicFeedKey($feedName, $compress),
        ]);
    }

    /**
     * @param FeedName $feedName
     * @param Compression|null $compress
     * @return void
     */
    public function publishTmpFeed(FeedName $feedName, ?Compression $compress = null): void
    {
        $this->client->copyObject([
            'Bucket' => $this->basketName,
            'Key' => $this->getPublicFeedKey($feedName, $compress),
            'CopySource' => $this->basketName . '/' . $this->getTmpFeedKey($feedName, $compress),
        ]);
    }

    /**
     * @param FeedName $feedName
     * @param Compression|null $compress
     * @return string
     */
    public function getETag(FeedName $feedName, ?Compression $compress = null): string
    {
        $headers = $this->client->headObject([
            'Bucket' => $this->basketName,
            'Key' => $this->getPublicFeedKey($feedName, $compress),
        ]);

        $headers = $headers->toArray();
        foreach ($headers as $key => $value) {
            if ($key === 'ETag' && is_string($value ?: null)) {
                return $value;
            }
        }

        return '';
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