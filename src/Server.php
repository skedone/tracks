<?php

namespace Tracks;

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use React\EventLoop\Factory;
use React\EventLoop\StreamSelectLoop;
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

        $this->storage = ClientBuilder::create()->setHosts(['127.0.0.1:9200'])->build();

        $this->remaining = $this->provider->lLen('tracks');

        $this->loop = Factory::create();
    }

    public function run()
    {

        $app = new Api($this->loop, $this->provider, $this->storage);
        $app->listen();

        $this->loop->addPeriodicTimer(0.000001, function() {
            $this->store();
        });

        $this->loop->run();
    }

    private function store()
    {
        $response = \json_decode($this->provider->lPop('tracks'));
        if($response) {
            $params = [
                'index' => 'tracks',
                'type' => 'event',
                'id' => $response->id,
                'body' => $response
            ];
            $return = $this->storage->index($params);
        }
    }

}