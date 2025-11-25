<?php

namespace App\DTO;

use App\Render\Contract\RenderInterface;
use App\Stream\Stream;

class FeedRenderData
{
    /**
     * @param Stream $stream
     * @param RenderInterface $render
     */
    public function __construct(
        private readonly Stream          $stream,
        private readonly RenderInterface $render
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