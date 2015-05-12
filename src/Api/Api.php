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
            $response->writeHead(200, array('Content-Type' => 'application/javascript'));
            $response->end(json_encode([
                'status' => 200,
                'queued' => $this->provider->count(),
                'memory' => (memory_get_usage(true) / 1024)
            ]));
        });

        return $this;
    }

    public function listen()
    {
        $this->socket->listen($this->port);
    }
}