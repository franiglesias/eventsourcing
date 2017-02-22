<?php

namespace Milhojas\EventSourcing\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Milhojas\EventSourcing\EventStore\DBALEventStore;
use Milhojas\EventSourcing\Utility\ConfigManager;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;

class SetUpCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('events:setup')
            ->setDescription('Creates event table in the database.')
            ->setHelp('This command allows you to create the needed events table in your databse.')
        ;
        $this->useConnection = getenv('ENV_EVENT_SOURCING');
        $this->manager = new ConfigManager();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->useConnection = $this->chooseConnection($input, $output);

        $connection = $this->getConnection();

        $store = new DBALEventStore($connection, 'events');
        $store->setUpStore();
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    public function chooseConnection(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');
        $question = new ChoiceQuestion(
            'Please select connection to use',
            $this->manager->getAvailableConnections(),
            0
        );
        $question->setErrorMessage('Connection %s is invalid.');

        return $helper->ask($input, $output, $question);
    }

    /**
     * @return \Doctrine\DBAL\Connection
     */
    private function getConnection()
    {
        $config = new Configuration();
        $connection = DriverManager::getConnection($this->manager->getConfiguration($this->useConnection), $config);

        return $connection;
    }
}
