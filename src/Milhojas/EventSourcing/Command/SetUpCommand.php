<?php

namespace Milhojas\EventSourcing\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Milhojas\EventSourcing\EventStore\DBALEventStore;
use Milhojas\EventSourcing\Utility\ConfigManager;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;
use Psr\Log\LoggerInterface;

class SetUpCommand extends Command
{
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var ConfigManager
     */
    private $manager;

    protected function configure()
    {
        $this
            ->setName('events:setup')
            ->setDescription('Creates event table in the database.')
            ->setHelp('This command allows you to create the needed events table in your databse.')
        ;
        $this->useConnection = getenv('ENV_EVENT_SOURCING');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getConfigurationManager($output);
        $this->useConnection = $this->chooseConnection($input, $output);
        $connection = $this->getConnection();
        $store = new DBALEventStore($connection, 'events');
        $store->setUpStore();
        $this->logger->notice('Event Store table created.');
    }

    /**
     * Prepares configuration manager.
     *
     * @param OutputInterface $output
     */
    public function getConfigurationManager(OutputInterface $output)
    {
        $this->logger = new ConsoleLogger($output);
        $this->manager = new ConfigManager($this->logger);
    }
    /**
     * Asks the user to choose a connection from all available.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    private function chooseConnection(InputInterface $input, OutputInterface $output)
    {
        $connections = $this->manager->getAvailableConnections();
        if (count($connections) == 1) {
            return array_shift($connections);
        }
        $helper = $this->getHelper('question');
        $question = new ChoiceQuestion(
            'Please select connection to use',
            $connections,
            0
        );
        $question->setErrorMessage('Connection %s is invalid.');

        return $helper->ask($input, $output, $question);
    }

    /**
     * Prepares the connectoin with database using the selected configuration.
     *
     * @return \Doctrine\DBAL\Connection
     */
    private function getConnection()
    {
        $config = new Configuration();
        $connection = DriverManager::getConnection($this->manager->getConfiguration($this->useConnection), $config);
        $this->logger->notice(sprintf('Using connection \'%s\'.', $this->useConnection));

        return $connection;
    }
}
