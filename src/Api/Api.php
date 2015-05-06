<?php

namespace Tracks\Api;

class Api {

    private $remaining = 0;

    private $socket;

    public function __construct($loop, $provider, $storage)
    {
        $this->socket = new \React\Socket\Server($loop);
        $this->port = 1337;
        $this->provider = $provider;
        $this->storage = $storage;

        $http = new \React\Http\Server($this->socket);
        $http->on('request', function ($request, $response) {
            $response->writeHead(200, array('Content-Type' => 'text/plain'));
            $response->end($this->debug());
        });

        return $this;
    }

    public function listen()
    {
        $this->socket->listen($this->port);
    }

    private function debug()
    {
        $current = $this->provider->lLen('tracks');
        $return = (memory_get_usage(true) / 1024) . "kb  " . ($current - $this->remaining) . " ( $current ) \n";
        $this->remaining = $this->provider->lLen('tracks');
        return $return;
    }
}