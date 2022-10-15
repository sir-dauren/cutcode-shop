<?php
declare(strict_types=1);

namespace App\Logging\Telegram;
use App\Logging\Telegram\TelegramLoggerHandler;
use Monolog\Logger;


final class TelegramLoggerFactory
{
	 public function __invoke(array $config): Logger
    {
       $logger = new Logger('telegram');
       $logger->pushHandler(new TelegramLoggerHandler($config));
 
       return $logger;
    }
}
