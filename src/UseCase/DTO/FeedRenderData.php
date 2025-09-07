<?php

namespace App\UseCase\DTO;

use App\UseCase\Render\BaseRender;
use App\UseCase\Stream\Stream;

class FeedRenderData
{
    /**
     * @param Stream $stream
     * @param BaseRender $render
     */
    public function __construct(
        private Stream $stream,
        private BaseRender $render
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
     * @return BaseRender
     */
    public function getRender(): BaseRender
    {
        return $this->render;
    }
}