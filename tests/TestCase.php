<?php

declare(strict_types=1);

namespace FiveOrbs\Error\Tests;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Psr\Http\Message\ServerRequestInterface;

class TestCase extends BaseTestCase
{
	protected ?int $defaultErrorReporting;
	protected mixed $defaultLog;
	protected mixed $tempFile;
	protected mixed $logFile;
	protected ?Psr17Factory $factory;

	public function setUp(): void
	{
		// capture output of error_log calls in a temporary file
		// to prevent it printed to the console.
		$this->defaultLog = ini_get('error_log');
		$this->tempFile = tmpfile();
		$this->logFile = stream_get_meta_data($this->tempFile)['uri'];
		ini_set('error_log', $this->logFile);

		$this->defaultErrorReporting = error_reporting();
		error_reporting(E_ALL);

		$this->factory = new Psr17Factory();
	}

	public function tearDown(): void
	{
		// Restore default error_log and handlers
		if (is_file($this->logFile)) {
			$logFileContent = file_get_contents($this->logFile);
			unlink($this->logFile);
		}

		ini_set('error_log', $this->defaultLog);
		restore_error_handler();
		restore_exception_handler();
		error_reporting($this->defaultErrorReporting);

		$this->defaultErrorReporting = null;
		$this->defaultLog = null;
		$this->tempFile = null;
		$this->logFile = null;

		$this->factory = null;

		if (getenv('ECHO_LOG') && $logFileContent) {
			error_log($logFileContent);
		}
	}

	public function throws(string $exception, string $message = null): void
	{
		$this->expectException($exception);

		if ($message) {
			$this->expectExceptionMessage($message);
		}
	}

	public function request(): ServerRequestInterface
	{
		$creator = new ServerRequestCreator(
			$this->factory, // ServerRequestFactory
			$this->factory, // UriFactory
			$this->factory, // UploadedFileFactory
			$this->factory,  // StreamFactory
		);

		return $creator->fromGlobals();
	}
}
