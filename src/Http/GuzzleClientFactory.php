<?php
/**
 * @author Oriam Corrales <ocorrales@blim.com>
 */

namespace Stocker\Http;


use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use Psr\Log\LoggerInterface;

class GuzzleClientFactory
{
    /**
     * @param string $identifier
     * @param array $config
     * @param LoggerInterface $logger
     * @return Client
     */
    static function create(string $identifier, array $config, LoggerInterface $logger = null): Client
    {
        $stack = HandlerStack::create();
        if($logger){
            $stack->push(
                Middleware::log(
                    $logger,
                    new MessageFormatter(MessageFormatter::DEBUG)
                )
            );
        }

        $clientConfig = [
            'base_uri' => $config[$identifier]['base_url'],
            'handler' => $stack,
            'headers' => $config[$identifier]['headers'] ?? []
        ];

        return new Client($clientConfig);
    }
}