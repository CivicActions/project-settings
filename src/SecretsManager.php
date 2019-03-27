<?php

namespace Kducharm\ProjectSettings;

use Kducharm\ProjectSettings\Constants\ProjectEnvironmentTypes;

/**
 * Class SecretsManager
 * @package Kducharm\ProjectSettings
 */
abstract class SecretsManager
{
    const PROJECT_SETTINGS_SECRETS_PROVIDER_CLASS_OVERRIDE = 'PROJECT_SETTINGS_SECRETS_PROVIDER_CLASS';

    protected $project_settings;
    protected $project_prefix;

    /*
     * Format is:
     *   'SECRET_NAME' => [
     *     'provider_class_name' => [ 'optional_key_for_provider_data' => 'value_for_provider_data' ],
     *   ];
     */
    protected $secret_definitions = [];

    // Default secret provider.
    protected $secret_provider_class = 'EnvSecretsProvider';

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
        if (empty($this->getSecretDefinitions())) {
            throw new \Exception('No secret definitions!');
        }

        $this->validateSecretDefintions();

        $this->project_settings = $project_settings;
        $secrets_provider_class_override = getenv(self::PROJECT_SETTINGS_SECRETS_PROVIDER_CLASS_OVERRIDE);
        if (!empty($secrets_provider_class_override)) {
            // Validate secrets provider class exists.
            if (!class_exists('Kducharm\ProjectSettings\SecretsProviders\\' . $secrets_provider_class_override)) {
                throw new \Exception("{$secrets_provider_class_override} Secrets Provider Class does not exist!");
            }
            $this->setSecretProviderClass($secrets_provider_class_override);
        }
    }

    /**
     * @return array|false|string
     */
    public function getSecretProviderClass()
    {
        return $this->secret_provider_class;
    }

    /**
     * @param array|false|string $secret_provider_class
     */
    public function setSecretProviderClass($secret_provider_class)
    {
        $this->secret_provider_class = $secret_provider_class;
    }

    /**
     * @return array
     */
    public function getSecretDefinitions()
    {
        return $this->secret_definitions;
    }

    /**
     * @param array $secret_definitions
     */
    public function setSecretDefinitions($secret_definitions)
    {
        $this->secret_definitions = $secret_definitions;
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
     * Get Secret.
     *
     * @param string $secret_name
     *   Valid secret name defined in $secret_definitions.
     * @param bool $bypass_prefix
     *   If TRUE, bypasses PROJECT_NAME and ENV prefixes.
     * @return string
     *   Secret value.
     * @throws \Exception
     */
    public function getSecret($secret_name, $bypass_prefix = false)
    {
        // Check this a valid secret name.
        if (in_array($secret_name, array_keys($this->getSecretDefinitions()))) {
            $secret_definition = $this->getSecretDefinitions()[$secret_name];

            // Return the override value if set.
            if (isset($secret_definition['value'])) {
                return $secret_definition['value'];
            }


            $secrets_provider_class = 'Kducharm\ProjectSettings\SecretsProviders\\';
            if (isset($secret_definition['secrets_provider_class'])) {
                $secrets_provider_class .= $secret_definition['secrets_provider_class'];
            } else {
                $secrets_provider_class .= $this->getSecretProviderClass();
            }
            $secrets_provider = new $secrets_provider_class($this);
            try {
                if (isset($secret_definition['bypass_prefix']) && $secret_definition['bypass_prefix']) {
                    $bypass_prefix = true;
                }

                $secret_value = $secrets_provider->getSecretValue($secret_name, $bypass_prefix);
                return $secret_value;
            } catch (\Exception $e) {
                // @todo - Currently outputting error message only so it doesn't disrupt on unset secrets.
                echo $e->getMessage() . "\nSecret: {$secret_name}  Bypass Prefix: " . ($bypass_prefix ? 1:0) . "\n";
                return null;
            }
        } else {
            throw new \Exception("Secret {$secret_name} not a valid secret name (undefined in secret definitions).");
        }
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
            foreach ($this->getSecretDefinitions() as $secret_name => $secret_definition) {
                $valid_secret_names[] = $this->getProjectPrefix() . strtoupper($env_type) . '_' . $secret_name;
            }
        }

        // Allow non-env specific secret names.
        foreach ($this->getSecretDefinitions() as $secret_name => $secret_definition) {
            $valid_secret_names[] = $this->getProjectPrefix() . $secret_name;
        }

        return $valid_secret_names;
    }


    /**
     * Validates each secret definition that has a bundle also has a key defined.
     * @throws \Exception
     */
    public function validateSecretDefintions()
    {
        foreach ($this->getSecretDefinitions() as $secret_name => $secret_definition) {
            if (isset($secret_definition['bundle'])) {
                if (empty($secret_definition['bundle']) || !isset($secret_definition['key']) || empty($secret_definition['key'])) {
                    throw new \Exception("Bundle or key is not set for secret definition {$secret_name}");
                }
            }
        }
    }
}
