<?php

namespace App\UseCase\DTO;

use App\UseCase\Render\Contract\RenderInterface;
use App\UseCase\Stream\Stream;

class FeedRenderData
{
    /**
     * @param Stream $stream
     * @param RenderInterface $render
     */
    public function __construct(
        private Stream          $stream,
        private RenderInterface $render
    )
    {
    }

    /**
     * @return Stream
     */
    public function getStream(): Stream
    {
        return $this->stream;
    }

    /**
     * @return RenderInterface
     */
    public function getRender(): RenderInterface
    {
        return $this->render;
    }
}