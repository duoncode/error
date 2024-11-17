<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use FiveOrbs\Error\Handler;
use FiveOrbs\Log\Logger;
use Nyholm\Psr7\Factory\Psr17Factory;

$handler = new Handler(new Psr17Factory());
$handler->logger(new Logger('/tmp/logfile.log'));
