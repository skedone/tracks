<?php

namespace Tracks\Command;

use Elasticsearch\ClientBuilder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MappingCommand extends Command {
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('map')
            ->setDescription('Map everything')
        ;
    }
    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $storage = ClientBuilder::create()->setHosts(['127.0.0.1:9200'])->setRetries(1)->build();
        $storage->indices()->delete(['index' => 'tracking']);
        $storage->indices()->create($this->getMapping());

    }

    private function getMapping()
    {
        return [
            'index' => 'tracking',
            'body' => [
                'settings' => [
                    '_source' => array(
                        'enabled' => true
                    ),
                ],
                'mappings' => [
                    'event' => [
                        '_timestamp' => [
                            'enabled' => true,
                            'path' => 'tc',
                            'format' => 'date_time'
                        ],
                        'properties' => [
                            'ts' => [
                                'type' => 'date',
                                'format' => 'date_time'
                            ],
                            'te' => [
                                'type' => 'date',
                                'format' => 'date_time'
                            ],
                            'ms' => [
                                'type' => 'integer'
                            ],
                            'tags' => [
                                'type' => 'string',
                                'index_name' => 'tag'
                            ],
                            'host' => [
                                'type' => 'string'
                            ],
                            'payload' => [
                                'type' => 'object',
                                'enabled' => 'false'
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }
}