<?php

namespace Tracks;

use Psr\Log\LoggerInterface;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
use React\EventLoop\StreamSelectLoop;
use React\EventLoop\Timer\Timer;

use Tracks\Api\Api;
use Tracks\Provider\ProviderInterface;
use Tracks\Storage\Exception\StorageFormatException;
use Tracks\Storage\Exception\StorageUnavailableException;
use Tracks\Storage\StorageInterface;

class Server {

    /** @var StorageInterface  */
    private $storage;

    /** @var ProviderInterface */
    private $provider;

    /** @var LoopInterface  */
    private $loop;

    /** @var LoggerInterface  */
    private $logger;

    /**
     * @param LoopInterface $loopInterface
     * @param ProviderInterface $providerInterface
     * @param StorageInterface $storageInterface
     * @param LoggerInterface $loggerInterface
     */
    public function __construct(
        LoopInterface $loopInterface, ProviderInterface $providerInterface, StorageInterface $storageInterface,
        LoggerInterface $loggerInterface
    )
    {
        $this->storage = $storageInterface;
        $this->provider = $providerInterface;
        $this->remaining = $this->provider->count();
        $this->loop = $loopInterface;
        $this->logger = $loggerInterface;
    }

    public function run()
    {

        $this->logger->info('Started..');
        $app = new Api($this->loop, $this->provider, $this->storage);
        $app->listen();


        $this->loop->addPeriodicTimer(0.000001, function(Timer $timer) {
            $event = $this->provider->pop();
            try {
                $this->storage->store($event);
            } catch(StorageFormatException $e) {
                $this->provider->error($event);
            } catch(StorageUnavailableException $e) {
                $this->provider->error($event);
                throw new \Exception("Storage went away.. sorry.");
            }
        });

        $this->loop->addPeriodicTimer(10, function(){
            $this->logger->debug('Memory usage ' . (memory_get_usage(true) / 1024) . 'Kb');
        });

        $this->loop->run();
    }

}