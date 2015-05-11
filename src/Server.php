<?php

namespace Tracks;

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

    public function __construct(LoopInterface $loopInterface, ProviderInterface $providerInterface, StorageInterface $storageInterface)
    {
        $this->storage = $storageInterface;
        $this->provider = $providerInterface;
        $this->remaining = $this->provider->count();
        $this->loop = $loopInterface;

    }

    public function run()
    {

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

        $this->loop->addPeriodicTimer(1, function(){
            echo "STATS " . (memory_get_usage(true) / 1024) . ' Kb' . PHP_EOL;
        });

        $this->loop->run();
    }

}