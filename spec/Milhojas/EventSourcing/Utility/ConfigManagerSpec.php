<?php

namespace spec\Milhojas\EventSourcing\Utility;

use Milhojas\EventSourcing\Utility\ConfigManager;
use PhpSpec\ObjectBehavior;
use Symfony\Component\Yaml\Yaml;
use org\bovigo\vfs\vfsStream;

class ConfigManagerSpec extends ObjectBehavior
{
    public function let()
    {
        $this->beConstructedWith();
        $this->setDefaultConfigFiles([$this->getConfigFile()]);
    }
    public function it_is_initializable()
    {
        $this->shouldHaveType(ConfigManager::class);
    }

    public function it_can_locate_default_configuration_file()
    {
        $this->getAvailableConnections()->shouldBeArray();
    }

    public function it_can_return_a_list_of_availble_connections()
    {
        $this->getAvailableConnections()->shouldBe(['test', 'production']);
    }

    public function it_throws_exception_if_invalid_connection()
    {
        $this->shouldThrow(\InvalidArgumentException::class)->during('getConfiguration', ['invalid']);
    }

    public function it_throws_exception_if_invalid_configuration_file()
    {
        $this->setDefaultConfigFiles([$this->getInvalidFile()]);
        $this->shouldThrow(\InvalidArgumentException::class)->during('getConfiguration', ['test']);
    }

    public function it_can_return_options_for_a_given_connection()
    {
        $this->getConfiguration('test')->shouldBe([
            'driver' => 'pdo_mysql',
            'user' => 'root',
            'password' => 'root',
            'dbname' => 'testmilhojas',
            'host' => 'localhost',
            'charset' => 'utf8mb4',
        ]);
    }

    private function getConfigFile()
    {
        $config = [
            'doctrine' => [
                'dbal' => [
                    'default_connection' => 'test',
                    'connections' => [
                        'test' => [
                            'driver' => 'pdo_mysql',
                            'user' => 'root',
                            'password' => 'root',
                            'dbname' => 'testmilhojas',
                            'host' => 'localhost',
                            'charset' => 'utf8mb4',
                        ],
                        'production' => [
                            'driver' => 'pdo_mysql',
                            'user' => 'user',
                            'password' => 'pwd',
                            'dbname' => 'milhojas',
                            'host' => 'localhost',
                            'charset' => 'utf8mb4',
                        ],

                    ],
                ],
            ],
        ];
        $fileSystem = vfsStream::setUp('root', 0, []);
        $file = vfsStream::newFile('config/database.yml')
            ->withContent(Yaml::dump($config))
            ->at($fileSystem);

        return $file->url();
    }

    public function getInvalidFile()
    {
        $config = [
            'dbal' => [
                'default_connection' => 'test',
                'connections' => [
                    'test' => [
                        'driver' => 'pdo_mysql',
                        'user' => 'root',
                        'password' => 'root',
                        'dbname' => 'testmilhojas',
                        'host' => 'localhost',
                        'charset' => 'utf8mb4',
                    ],
                    'production' => [
                        'driver' => 'pdo_mysql',
                        'user' => 'user',
                        'password' => 'pwd',
                        'dbname' => 'milhojas',
                        'host' => 'localhost',
                        'charset' => 'utf8mb4',
                    ],

                ],
            ],
        ];
        $fileSystem = vfsStream::setUp('root', 0, []);
        $file = vfsStream::newFile('config/invalid.yml')
            ->withContent(Yaml::dump($config))
            ->at($fileSystem);

        return $file->url();
    }
}
