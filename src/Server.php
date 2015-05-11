<?php

namespace Tracks;

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Elasticsearch\Common\Exceptions\BadRequest400Exception;
use Elasticsearch\Common\Exceptions\NoNodesAvailableException;
use React\EventLoop\Factory;
use React\EventLoop\StreamSelectLoop;
use React\EventLoop\Timer\Timer;
use Tracks\Api\Api;

class Server {

    /** @var int */
    private $remaining;

    /** @var \Redis */
    private $provider;

    /** @var Client */
    private $storage;

    /** @var StreamSelectLoop */
    private $loop;

    public function __construct()
    {
        $this->provider = new \Redis();
        $this->provider->connect('127.0.0.1');

        $this->storage = ClientBuilder::create()->setHosts(['127.0.0.1:9200'])->setRetries(1)->build();

        $this->remaining = $this->provider->lLen('tracks');

        $this->loop = Factory::create();

    }

    public function run()
    {

        $app = new Api($this->loop, $this->provider, $this->storage);
        $app->listen();


        $this->loop->addPeriodicTimer(0.000001, function(Timer $timer) {
            $this->store();
        });

        $this->loop->addPeriodicTimer(1, function(){
            echo "STATS " . (memory_get_usage(true) / 1024) . ' Kb' . PHP_EOL;
        });

        $this->loop->run();
    }

    private function store()
    {
        $response = \json_decode($this->provider->lPop('tracks'));
        if($response) {
            $response->ts = (string) $response->ts;
            $response->te = (string) $response->te;
            $params = [
                'index' => 'tracking',
                'type' => 'event',
                'id' => $response->id,
                'body' => $response
            ];

            try {
                $return = $this->storage->index($params);
            } catch(NoNodesAvailableException $e) {
                $this->provider->rPush('tracks', \json_encode($response));
                throw new \Exception("Elasticsearch went away..");
            } catch(BadRequest400Exception $e) {
                $this->provider->rPush('tracks.errors', \json_encode($response));
            }
        }
    }

}