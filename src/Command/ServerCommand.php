<?php

namespace Tracks\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ServerCommand extends Command {
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('run')
            ->setDefinition(array(
                new InputOption('config', 'c', InputOption::VALUE_OPTIONAL, 'Configuration file'),
                new InputOption('debug', null, InputOption::VALUE_NONE, 'Run with debug flags active, overriding debug flags')
            ))
            ->setDescription('Run server daemon in a nice dress.')
            ->setHelp(<<<EOF
The <info>run</info> command try to rule them all.
EOF
            )
        ;
    }
    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $server = new \Tracks\Server();
        $server->run();
    }
}