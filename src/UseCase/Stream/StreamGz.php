<?php

namespace App\UseCase\Stream;

use Exception;

class StreamGz
{
    /**
     * @param resource $stream
     */
    public function __construct(protected mixed $stream, protected bool $isTmp)
    {
        if (!is_resource($stream)) {
            throw new Exception('Invalid stream gz received in constructor');
        }
    }

    /**
     * @param string $path
     * @param string $mode
     * @return self
     */
    public static function fromPath(string $path, string $mode): self
    {
        $stream = gzopen($path, $mode);
        return new self($stream, false);
    }

    /**
     * @param string $content
     * @return void
     */
    public function write(string $content): void
    {
        if (!is_resource($this->stream)) {
            throw new Exception('Invalid gzip stream received in write');
        }

        if ($content) {
            gzwrite($this->stream, $content);
        }
    }

    /**
     * @return void
     */
    public function close(): void
    {
        if (is_resource($this->stream)) {
            gzclose($this->stream);
        }
    }
}