<?php

namespace App\Repository\Contract;

use App\Enum\Compression;
use App\Enum\FeedName;
use App\Stream\Stream;

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
     * @return void
     */
    public function deleteTmpFeed(FeedName $feedName, ?Compression $compress = null): void;

    /**
     * @param FeedName $feedName
     * @param Compression|null $compress
     * @return void
     */
    public function deletePublicFeed(FeedName $feedName, ?Compression $compress = null): void;

    /**
     * @param FeedName $feedName
     * @param Compression|null $compress
     * @return void
     */
    public function publishTmpFeed(FeedName $feedName, ?Compression $compress = null): void;

    /**
     * @param FeedName $feedName
     * @param Compression|null $compress
     * @return string
     */
    public function getETag(FeedName $feedName, ?Compression $compress = null): string;
}