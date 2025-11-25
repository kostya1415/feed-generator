<?php

namespace App\Stream;

use Exception;

class Stream
{
    /**
     * @param resource $stream
     * @throws Exception
     */
    public function __construct(
        private readonly mixed $stream,
        private readonly bool  $isTmp)
    {
        if (!is_resource($stream)) {
            throw new Exception('Invalid stream received in constructor');
        }
    }

    /**
     * @param string $path
     * @param string $mode
     * @return self
     * @throws Exception
     */
    public static function fromPath(string $path, string $mode): self
    {
        $stream = fopen($path, $mode);
        return new self($stream, false);
    }

    /**
     * @return self
     * @throws Exception
     */
    public static function tmp(): self
    {
        return new self(tmpfile(), true);
    }

    /**
     * @param string $content
     * @return void
     * @throws Exception
     */
    public function write(string $content): void
    {
        if (!is_resource($this->stream)) {
            throw new Exception('Invalid stream received in write');
        }

        if ($content) {
            fwrite($this->stream, $content);
        }
    }

    /**
     * @param int $offset
     * @return void
     * @throws Exception
     */
    public function resetPointer(int $offset = 0): void
    {
        if (!is_resource($this->stream)) {
            throw new Exception('Invalid stream received in resetPointer');
        }

        fseek($this->stream, $offset);
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getFilePath(): string
    {
        if (!is_resource($this->stream)) {
            throw new Exception('Invalid stream received in getFilePath');
        }

        $metaData = stream_get_meta_data($this->stream);
        $path = ($metaData['uri'] ?? null) ?: null;

        if (!is_string($path)) {
            throw new Exception('Invalid stream uri received in getFilePath');
        }

        return $path;
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function isEnd(): bool
    {
        if (!is_resource($this->stream)) {
            throw new Exception('Invalid stream received in isEnd');
        }

        return feof($this->stream);
    }

    /**
     * @param int $length
     * @return string
     * @throws Exception
     */
    public function read(int $length = 1024): string
    {
        if (!is_resource($this->stream)) {
            throw new Exception('Invalid stream received in read');
        }

        return fread($this->stream, $length) ?: '';
    }

    /**
     * @return void
     */
    public function close(): void
    {
        if (is_resource($this->stream)) {
            fclose($this->stream);
        }
    }

    /**
     * @return void
     * @throws Exception
     */
    public function delete(): void
    {
        if (!$this->isTmp) {
            $path = $this->getFilePath();
            $this->close();
            unlink($path);
        } else {
            $this->close();
        }
    }
}