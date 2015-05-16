<?php

namespace Tracks\Storage;


use Elasticsearch\ClientBuilder;
use Elasticsearch\Common\Exceptions\BadRequest400Exception;
use Elasticsearch\Common\Exceptions\NoNodesAvailableException;
use Psr\Log\LoggerInterface;
use Tracks\Storage\Exception\StorageFormatException;
use Tracks\Storage\Exception\StorageUnavailableException;

class ElasticsearchStorage implements StorageInterface {

    public function __construct()
    {
        $this->client = ClientBuilder::create()->setHosts(['127.0.0.1:9200'])->setRetries(1)->build();
    }

    public function store($event)
    {
        $response = $event;

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
                $return = $this->client->index($params);
            } catch(NoNodesAvailableException $e) {
                throw new StorageUnavailableException($e->getMessage());
            } catch(BadRequest400Exception $e) {
                throw new StorageFormatException($e->getMessage());
            }

            return true;
        }

        return $event;
    }

}