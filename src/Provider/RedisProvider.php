<?php

namespace Tracks\Provider;

class RedisProvider implements ProviderInterface {

    private $key = 'tracks';

    private $error_key = 'tracks::error';

    public function __construct()
    {
        $this->client = new \Redis();
        $this->client->connect('127.0.0.1');
    }

    /**
     * @return object
     */
    public function pop()
    {
        return \json_decode($this->client->lPop($this->key));
    }

    /**
     * @param array $data
     * @return int
     */
    public function push(array $data)
    {
        return $this->client->lPush($this->key, \json_encode($data));
    }

    /**
     * @param $data
     * @return int
     */
    public function error($data)
    {
        return $this->client->lPush($this->error_key, \json_encode($data));
    }

    /**
     * @return int
     */
    public function count()
    {
        return $this->client->lLen($this->key);
    }
}