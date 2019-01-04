<?php

namespace Kducharm\ProjectSettings;

use Kducharm\ProjectSettings\Constants\ProjectEnvironmentTypes;

/**
 * Class SecretsManager
 * @package Kducharm\ProjectSettings
 */
abstract class SecretsManager
{
    protected $project_settings;
    protected $project_prefix;
    protected $secret_definitions = [];

    /**
     * SecretsManager constructor.
     * @param ProjectSettings $project_settings
     * @throws \Exception
     */
    public function __construct($project_settings)
    {
        if (empty($this->project_prefix)) {
            throw new \Exception('No project prefix set!');
        }
        if (empty($this->secret_definitions)) {
            throw new \Exception('No secret definitions!');
        }

        $this->project_settings = $project_settings;
    }

    /**
     * @return string
     */
    public function getProjectPrefix()
    {
        return $this->project_prefix;
    }

    /**
     * @param string $project_prefix
     */
    public function setProjectPrefix($project_prefix)
    {
        $this->project_prefix = $project_prefix;
    }

    /**
     * @return ProjectSettings
     */
    public function getProjectSettings()
    {
        return $this->project_settings;
    }

    /**
     * Get Secret
     * @param string $secret_name
     *   Valid secret name defined in $secret_definitions.
     * @return string|null
     *   NULL if not found, secret value otherwise.
     */
    public function getSecret($secret_name)
    {
        // Check this a valid secret name.
        if (in_array($secret_name, $this->secret_definitions)) {
            // Check if env specific secret exists, else return non-specific.
            $env_secret_name = $this->getProjectPrefix() . $this->getEnvironmentPrefix() . $secret_name;
            if ($secret = getenv($env_secret_name)) {
                return $secret;
            } elseif ($secret = getenv($this->getProjectPrefix() . $secret_name)) {
                return $secret;
            }
        }
        return null;
    }

    /**
     * Get Environment Prefix (uppercase with _).
     * @return string
     *   Environment Prefix
     */
    public function getEnvironmentPrefix()
    {
        $env_prefix = $this->project_settings->getEnvironmentType();
        return strtoupper($env_prefix) . '_';
    }

   /**
     * Get Valid Secrets
     * @return array
     *   Array of Secret names
     */
    public function getValidSecrets()
    {
        $valid_secret_names = [];
        try {
            $env_types = ProjectEnvironmentTypes::getConstants();
        } catch (\ReflectionException $e) {
            echo $e->getMessage();
        }
        foreach ($env_types as $env_type) {
            foreach ($this->secret_definitions as $secret_definition) {
                $valid_secret_names[] = $this->getProjectPrefix() . strtoupper($env_type) . '_' . $secret_definition;
            }
        }
        return $valid_secret_names;
    }
}
