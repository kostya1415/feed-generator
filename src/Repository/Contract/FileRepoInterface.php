<?php

namespace App\Repository\Contract;

use App\Enum\Compression;
use App\Enum\FeedName;
use App\Stream\Stream;
use Aws\Result;

interface FileRepoInterface
{
    /**
     * @return void
     */
    public function prepare(): void;

    /**
     * @param FeedName $feedName
     * @param Compression|null $compress
     * @return Stream
     */
    public function openTmpFeedWrite(FeedName $feedName, ?Compression $compress = null): Stream;

    /**
     * @param FeedName $feedName
     * @param Compression|null $compress
     * @return Stream
     */
    public function openPublicFeedRead(FeedName $feedName, ?Compression $compress = null): Stream;

    /**
     * @param FeedName $feedName
     * @param Compression|null $compress
     * @return Result
     */
    public function deleteTmpFeed(FeedName $feedName, ?Compression $compress = null): Result;

    /**
     * @param FeedName $feedName
     * @param Compression|null $compress
     * @return Result
     */
    public function deletePublicFeed(FeedName $feedName, ?Compression $compress = null): Result;

    /**
     * @param FeedName $feedName
     * @param Compression|null $compress
     * @return Result
     */
    public function publishTmpFeed(FeedName $feedName, ?Compression $compress = null): Result;

    /**
     * @param FeedName $feedName
     * @param Compression|null $compress
     * @return Result
     */
    public function getPublicFeedHeaders(FeedName $feedName, ?Compression $compress = null): Result;
}