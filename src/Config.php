<?php

namespace Sokil\Mongo\Migrator;

/**
 * Migration config
 */
class Config
{
    const ENV_PARAMETER_PATTERN = '/%env\(([a-z-A-Z0-9_]+)\)%/';

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
     *
     * @throws \RuntimeException when param defined as env variable but env variable not defined
     */
    public function get($name)
    {
        $value = null;

        // get value
        if (false === strpos($name, '.')) {
            $value = isset($this->config[$name]) ? $this->config[$name] : null;
        } else {
            $value = $this->config;
            foreach (explode('.', $name) as $field) {
                if (!isset($value[$field])) {
                    return null;
                }

                $value = $value[$field];
            }
        }

        // replace value with env variable
        // @todo: iterate over array values and replace env patterns
        if (!is_array($value)) {
            if (preg_match(self::ENV_PARAMETER_PATTERN, $value, $matches)) {
                $envValue = getenv($matches[1]);
                if ($envValue === false) {
                    throw new \RuntimeException(sprintf(
                        'No environment variable found with name %s',
                        $matches[1]
                    ));
                }

                $value = $envValue;
            }
        }

        return $value;
    }

    /**
     * @return string
     */
    public function getMigrationsDir()
    {
        return rtrim($this->get('path.migrations'), '/');
    }

    /**
     * @return string|null
     */
    public function getDefaultEnvironment()
    {
        return $this->get('default_environment');
    }

    /**
     * @param string|null $environment
     *
     * @return string|null
     */
    public function getDefaultDatabaseName($environment = null)
    {
        if (!$environment) {
            $environment = $this->getDefaultEnvironment();
        }
        
        return $this->get(sprintf('environments.%s.default_database', $environment));
    }

    /**
     * @param string|null $environment
     *
     * @return string|null
     */
    public function getDsn($environment = null)
    {
        if (!$environment) {
            $environment = $this->getDefaultEnvironment();
        }

        return $this->get(sprintf('environments.%s.dsn', $environment));
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

        $connectOptions = $this->get(sprintf('environments.%s.connectOptions', $environment));
        if (empty($connectOptions)) {
            $connectOptions = array();
        }

        return $connectOptions;
    }

    /**
     * @param string|null $environment
     *
     * @return string|null
     */
    public function getLogDatabaseName($environment = null)
    {
        if (!$environment) {
            $environment = $this->getDefaultEnvironment();
        }

        return $this->get(sprintf('environments.%s.log_database', $environment));
    }

    /**
     * @param string|null $environment
     *
     * @return string|null
     */
    public function getLogCollectionName($environment = null)
    {
        if (!$environment) {
            $environment = $this->getDefaultEnvironment();
        }

        return $this->get(sprintf('environments.%s.log_collection', $environment));
    }
}
