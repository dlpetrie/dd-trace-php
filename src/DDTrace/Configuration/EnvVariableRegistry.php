<?php

namespace DDTrace\Configuration;

/**
 * Registry that holds configuration properties and that is able to recover configuration values from environment
 * variables.
 */
class EnvVariableRegistry implements Registry
{
    /**
     * @var array
     */
    private $registry;

    public function __construct()
    {
        $this->registry = [];
    }

    /**
     * Return an env variable that starts with "DD_".
     *
     * @param string $key
     * @return string|null
     */
    public static function get($key)
    {
        $value = getenv(self::convertKeyToEnvVariableName($key));
        if (false === $value) {
            return null;
        }
        return trim($value);
    }

    /**
     * {@inheritdoc}
     */
    public function stringValue($key, $default)
    {
        if (isset($this->registry[$key])) {
            return $this->registry[$key];
        }
        $value = self::get($key);
        if (null !== $value) {
            return $this->registry[$key] = $value;
        }
        return $default;
    }

    /**
     * {@inheritdoc}
     */
    public function boolValue($key, $default)
    {
        if (isset($this->registry[$key])) {
            return $this->registry[$key];
        }

        $value = self::get($key);
        if (null === $value) {
            return $default;
        }

        $value = strtolower($value);
        if ($value === '1' || $value === 'true') {
            $this->registry[$key] = true;
        } elseif ($value === '0' || $value === 'false') {
            $this->registry[$key] = false;
        } else {
            return $default;
        }

        return $this->registry[$key];
    }

    /**
     * {@inheritdoc}
     */
    public function floatValue($key, $default, $min = null, $max = null)
    {
        if (!isset($this->registry[$key])) {
            $value = self::get($key);
            $value = strtolower($value);
            if (is_numeric($value)) {
                $floatValue = (float)$value;
            } else {
                $floatValue = (float)$default;
            }

            if (null !== $min && $floatValue < $min) {
                $floatValue = $min;
            }

            if (null !== $max && $floatValue > $max) {
                $floatValue = $max;
            }

            $this->registry[$key] = $floatValue;
        }

        return $this->registry[$key];
    }

    /**
     * {@inheritdoc}
     */
    public function inArray($key, $name)
    {
        if (!isset($this->registry[$key])) {
            $value = self::get($key);
            if (null !== $value) {
                $disabledIntegrations = explode(',', $value);
                $this->registry[$key] = array_map(function ($entry) {
                    return strtolower(trim($entry));
                }, $disabledIntegrations);
            } else {
                $this->registry[$key] = [];
            }
        }

        return in_array(strtolower($name), $this->registry[$key], true);
    }

    /**
     * Given a dot separated key, it converts it to an expected variable name.
     *
     * e.g.: 'distributed_tracing' -> 'DD_DISTRIBUTED_TRACING'
     *
     * @param string $key
     * @return string
     */
    private static function convertKeyToEnvVariableName($key)
    {
        return 'DD_' . strtoupper(str_replace('.', '_', trim($key)));
    }
}
