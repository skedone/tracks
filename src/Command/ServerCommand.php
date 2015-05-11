<?php

namespace Tracks\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

class ServerCommand extends Command {
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('run')
            ->setDefinition(array(
                new InputOption('configuration', 'c', InputOption::VALUE_REQUIRED, 'Configuration file'),
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

        $container = new ContainerBuilder();
        $configurationDirectory = new FileLocator($input->getOption('configuration'));

        $loader = new XmlFileLoader($container, $configurationDirectory);
        $loader->load('tracks.xml');

        $server = $container->get('tracks.server');
        $server->run();

    }
}