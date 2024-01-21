<?php

declare(strict_types=1);

namespace Conia\Error;

use ErrorException;
use Psr\Http\Message\ResponseFactoryInterface as ResponseFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Log\LoggerInterface as Logger;
use Throwable;

/** @psalm-api */
class Handler implements Middleware
{
    protected ?Logger $logger = null;

    /** @var RendererEntry[] */
    protected array $renderers = [];

    public function __construct(
        protected readonly ResponseFactory $responseFactory,
        protected readonly bool $debug = false,
    ) {
        set_error_handler([$this, 'handleError'], E_ALL);
        set_exception_handler([$this, 'emitException']);
    }

    public function __destruct()
    {
        restore_error_handler();
        restore_exception_handler();
    }

    public function logger(?Logger $logger = null): void
    {
        $this->logger = $logger;
    }

    public function process(Request $request, RequestHandler $handler): Response
    {
        try {
            return $handler->handle($request);
        } catch (Throwable $e) {
            return $this->getResponse($e, $request);
        }
    }

    /**
     * @param class-string<Throwable>|class-string<Throwable>[] $exceptions
     */
    public function render(string|array $exceptions, Renderer $renderer): RendererEntry
    {
        $renderEntry = new RendererEntry(is_string($exceptions) ? [$exceptions] : $exceptions, $renderer);
        $this->renderers[] = $renderEntry;

        return $renderEntry;
    }

    public function handleError(
        int $level,
        string $message,
        string $file = '',
        int $line = 0,
    ): bool {
        if ($level & error_reporting()) {
            throw new ErrorException($message, $level, $level, $file, $line);
        }

        return false;
    }

    public function emitException(Throwable $exception): void
    {
        $response = $this->getResponse($exception, null);

        echo (string)$response->getBody();
    }

    public function getResponse(Throwable $exception, ?Request $request): Response
    {
        $renderer = null;
        $logLevel = null;

        foreach ($this->renderers as $rendererEntry) {
            if ($rendererEntry->matches($exception)) {
                $renderer = $rendererEntry->renderer;
                $logLevel = $rendererEntry->getLogLevel();
                break;
            }
        }

        if (!is_null($logLevel)) {
            $this->log($logLevel, $exception);
        }

        if ($renderer) {
            return $renderer->render(
                $exception,
                $this->responseFactory->createResponse(),
                $request,
                $this->debug,
            );
        }

        if ($this->debug) {
            throw $exception;
        }

        $this->logUnmatched($exception);

        $response = $this->responseFactory->createResponse(500)->withHeader('Content-Type', 'text/html') ;
        $response->getBody()->write('<h1>500 Internal Server Error</h1>');

        return $response;
    }

    protected function log(string|int $logLevel, Throwable $exception): void
    {
        if ($this->logger) {
            $this->logger->log($logLevel, 'Matched Exception:', ['exception' => $exception]);
        }
    }

    protected function logUnmatched(Throwable $exception): void
    {
        if ($this->logger) {
            $this->logger->alert('Unmatched Exception:', ['exception' => $exception]);
        }
    }
}
