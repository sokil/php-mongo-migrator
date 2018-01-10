<?php

namespace Sokil\Mongo\Migrator;

/**
 * Migration config
 */
class Config
{
    /**
     * @var array
     */
    private $config;

    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * @param $name
     *
     * @return mixed|null
     */
    public function __get($name)
    {
        return isset($this->config[$name]) ? $this->config[$name] : null;
    }

    /**
     * @param string $name
     *
     * @return array|string|int|null
     */
    public function get($name)
    {
        if (false === strpos($name, '.')) {
            return isset($this->config[$name]) ? $this->config[$name] : null;
        }

        $value = $this->config;
        foreach (explode('.', $name) as $field) {
            if (!isset($value[$field])) {
                return null;
            }

            $value = $value[$field];
        }

        return $value;
    }

    /**
     * @return string
     */
    public function getMigrationsDir()
    {
        return rtrim($this->config['path']['migrations'], '/');
    }

    /**
     * @return string
     */
    public function getDefaultEnvironment()
    {
        return $this->config['default_environment'];
    }

    /**
     * @param string|null $environment
     *
     * @return string
     */
    public function getDefaultDatabaseName($environment = null)
    {
        if (!$environment) {
            $environment = $this->getDefaultEnvironment();
        }
        
        return $this->config['environments'][$environment]['default_database'];
    }

    /**
     * @param string|null $environment
     *
     * @return string
     */
    public function getDsn($environment = null)
    {
        if (!$environment) {
            $environment = $this->getDefaultEnvironment();
        }
        
        return $this->config['environments'][$environment]['dsn'];
    }

    /**
     * @param string|null $environment
     *
     * @return array
     */
    public function getConnectOptions($environment = null)
    {
        if (!$environment) {
            $environment = $this->getDefaultEnvironment();
        }

        return isset($this->config['environments'][$environment]['connectOptions'])
            ? $this->config['environments'][$environment]['connectOptions']
            : array();
    }

    /**
     * @param string|null $environment
     *
     * @return string
     */
    public function getLogDatabaseName($environment = null)
    {
        if (!$environment) {
            $environment = $this->getDefaultEnvironment();
        }
        
        return $this->config['environments'][$environment]['log_database'];
    }

    /**
     * @param string|null $environment
     *
     * @return string
     */
    public function getLogCollectionName($environment = null)
    {
        if (!$environment) {
            $environment = $this->getDefaultEnvironment();
        }
        
        return $this->config['environments'][$environment]['log_collection'];
    }
}
