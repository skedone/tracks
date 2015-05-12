<?php

namespace Tracks;

use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;
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

    /** @var Signals */
    private $signals;

    /** @var bool */
    private $isStoppable = FALSE;
     /** @var bool */
    private $isStopped = FALSE;

    /**
     * @param LoopInterface $loopInterface
     * @param ProviderInterface $providerInterface
     * @param StorageInterface $storageInterface
     * @param LoggerInterface $loggerInterface
     */
    public function __construct(
        LoopInterface $loopInterface, ProviderInterface $providerInterface,
        StorageInterface $storageInterface, LoggerInterface $loggerInterface,
        Signals $signals
    )
    {
        $this->storage = $storageInterface;
        $this->provider = $providerInterface;
        $this->loop = $loopInterface;
        $this->logger = $loggerInterface;
        $this->signals = $signals;

    }

    public function run()
    {
        $this->logger->info('Starting with PID ' . getmypid());
        $this->signals->on(SIGINT, function(){
            $this->stop();
        });

        $this->start();
        $this->loop->run();
    }

    public function stop()
    {
        $this->isStoppable = TRUE;
        $this->loop->addPeriodicTimer(0.000001, function(Timer $timer) {
            if ($this->isStopped) {
                $this->logger->alert('Stopping the server.');
                $this->loop->stop();
            }
        });
    }

    public function start()
    {
        $app = new Api($this->loop, $this->provider, $this->storage);
        $app->listen();

        $this->logger->info('Server started correctly.');

        $this->loop->addPeriodicTimer(0.000001, function(Timer $timer) {
            if($this->isStoppable) {
                $this->logger->info('Do not store anymore.');
                $this->isStopped = TRUE;
            }

            $this->storeEvent();
        });

        $this->loop->addPeriodicTimer(10, function(){
            $this->logger->debug('Memory usage ' . (memory_get_peak_usage(true) / 1024) . 'Kb');
        });
    }

    public function storeEvent()
    {
        $event = $this->provider->pop();
        if($event !== NULL) {
            try {
                $this->storage->store($event);
                $this->logger->debug('A new event is stored', [\json_encode($event)]);
            } catch(StorageFormatException $e) {
                $this->provider->error($event);
            } catch(StorageUnavailableException $e) {
                $this->provider->error($event);
                throw new \Exception("Storage went away.. sorry.");
            }
        }
    }
}