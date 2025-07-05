<?php

declare(strict_types=1);

namespace Mammatus\OpenTelemetry;

use Ancarda\Psr7\StringStream\ReadOnlyStringStream;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use React\Filesystem\AdapterInterface;
use React\Filesystem\Factory;
use React\Stream\ReadableResourceStream;

use function React\Async\await;
use function React\Promise\Stream\buffer;

final readonly class PsrStreamFactory implements StreamFactoryInterface
{
    private AdapterInterface $filesystem;

    public function __construct()
    {
        $this->filesystem = Factory::create();
    }

    public function createStream(string $content = ''): StreamInterface
    {
        return new ReadOnlyStringStream($content);
    }

    public function createStreamFromFile(string $filename, string $mode = 'r'): StreamInterface
    {
        return new ReadOnlyStringStream(await($this->filesystem->file($filename)->getContents()));
    }

    /** @param resource $resource */
    public function createStreamFromResource($resource): StreamInterface
    {
        return new ReadOnlyStringStream(await(buffer(new ReadableResourceStream($resource))));
    }
}
