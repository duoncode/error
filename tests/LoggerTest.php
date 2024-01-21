<?php

declare(strict_types=1);

namespace Conia\Error\Tests;

use Conia\Error\Handler;
use Conia\Error\Tests\Fixtures\TestRenderer;
use Conia\Log\Logger;
use ErrorException;
use PHPUnit\Framework\Attributes\TestDox;

class LoggerTest extends TestCase
{
    #[TestDox("Render matched error while using a logger")]
    public function testRenderMatchedErrorWithLogger(): void
    {
        $handler = new Handler($this->factory);
        $handler->logger(new Logger(logfile: $this->logFile));
        $handler->render(ErrorException::class, new TestRenderer())->log(Logger::CRITICAL);
        $response = $handler->getResponse(new ErrorException('test message'), $this->request());
        $output = file_get_contents($this->logFile);

        $this->assertEquals('rendered GET test message', (string)$response->getBody());
        $this->assertStringContainsString('CRITICAL: Matched Exception', $output);
    }

    #[TestDox("Render matched error while using a logger but no log level set")]
    public function testRenderMatchedErrorWithLoggerNoLevel(): void
    {
        $handler = new Handler($this->factory);
        $handler->logger(new Logger(logfile: $this->logFile));
        $handler->render(ErrorException::class, new TestRenderer());
        $response = $handler->getResponse(new ErrorException('test message'), $this->request());
        $output = file_get_contents($this->logFile);

        $this->assertEquals('rendered GET test message', (string)$response->getBody());
        $this->assertEquals('', $output);
    }

    #[TestDox("Render unmatched error while using a logger")]
    public function testRenderUnmatchedErrorWithLogger(): void
    {
        $handler = new Handler($this->factory);
        $handler->logger(new Logger(logfile: $this->logFile));
        $response = $handler->getResponse(new ErrorException('test message'), $this->request());
        $output = file_get_contents($this->logFile);

        $this->assertEquals('<h1>500 Internal Server Error</h1>', (string)$response->getBody());
        $this->assertStringContainsString('ALERT: Unmatched Exception', $output);
    }
}
