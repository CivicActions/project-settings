<?php

namespace Kducharm\ProjectSettings;

use Kducharm\ProjectSettings\Constants\ProjectEnvironmentTypes;
use Kducharm\ProjectSettings\SecretsProviders\EnvSecretsProvider;

/**
 * Class SecretsManager
 * @package Kducharm\ProjectSettings
 */
abstract class SecretsManager
{
    const PROJECT_SETTINGS_SECRETS_PROVIDER_CLASS_OVERRIDE = 'PROJECT_SETTINGS_SECRETS_PROVIDER_CLASS';

    protected $project_settings;
    protected $project_name;

    protected $secret_definitions = [];

    protected $valid_secret_names = [];

    // Default secret provider.
    protected $secret_provider_class = 'EnvSecretsProvider';

    public $env_secrets_provider;

    /**
     * SecretsManager constructor.
     * @param ProjectSettings $project_settings
     * @throws \Exception
     */
    public function __construct($project_settings)
    {
        if (empty($this->getProjectName())) {
            throw new \Exception('No project name set!');
        }
        if (empty($this->getSecretDefinitions())) {
            throw new \Exception('No secret definitions!');
        }

        $this->validateSecretDefinitions();

        $this->project_settings = $project_settings;
        $secrets_provider_class_override = getenv(self::PROJECT_SETTINGS_SECRETS_PROVIDER_CLASS_OVERRIDE);
        if (!empty($secrets_provider_class_override)) {
            // Validate secrets provider class exists.
            if (!class_exists('Kducharm\ProjectSettings\SecretsProviders\\' . $secrets_provider_class_override)) {
                throw new \Exception("{$secrets_provider_class_override} Secrets Provider Class does not exist!");
            }
            $this->setSecretProviderClass($secrets_provider_class_override);
        }
        $this->env_secrets_provider = new EnvSecretsProvider($this);

        $this->valid_secret_names = $this->getValidSecrets($this->getProjectSettings()->getEnvironmentType());
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
     * Get Project Name.
     *
     * @return string
     *   Project Name
     */
    public function getProjectName()
    {
        return $this->project_name;
    }

    /**
     * Set Project Name.
     *
     * @param string $project_name
     *   Project Name
     */
    public function setProjectName($project_name)
    {
        $this->project_name = $project_name;
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
     * @return string
     *   Secret value.
     * @throws \Exception
     */
    public function getSecret($secret_name)
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
                // Check if this part of a bundle.
                if (isset($secret_definition['bundle'])) {
                    $secret_bundle = $secrets_provider->getSecretValue($secret_definition['bundle']);
                    $secret_decoded = json_decode($secret_bundle, true);
                    $secret_value = isset($secret_decoded[$secret_definition['key']]) ? $secret_decoded[$secret_definition['key']] : null;
                } else {
                    $secret_value = $secrets_provider->getSecretValue($secret_name);
                }
                return $secret_value;
            } catch (\Exception $e) {
                // @todo - Currently outputting error message only so it doesn't disrupt on unset secrets.
                echo $e->getMessage() . "\nSecret: {$secret_name}\n";
                return null;
            }
        } else {
            throw new \Exception("Secret {$secret_name} not a valid secret name (undefined in secret definitions).");
        }
    }

   /**
     * Get Valid Secrets
     * @return array
     *   Array of Secret names
     */
    public function getValidSecrets()
    {
        $valid_secret_names = [];

        foreach ($this->getSecretDefinitions() as $secret_name => $secret_definition) {
            $valid_secret_names[] = $this->env_secrets_provider->getSecretPath($secret_name);
            $valid_secret_names[] = $this->env_secrets_provider->getSecretPath($secret_name, 1);
        }

        // Allow querying of bundle names.
        $bundles = array_unique(array_column($this->getSecretDefinitions(), 'bundle'));

        foreach ($bundles as $bundle) {
            $valid_secret_names[] = $this->env_secrets_provider->getSecretPath($bundle);
            $valid_secret_names[] = $this->env_secrets_provider->getSecretPath($bundle, 1);
        }

        sort($valid_secret_names);
        return $valid_secret_names;
    }


    /**
     * Validates each secret definition that has a bundle also has a key defined.
     * @throws \Exception
     */
    public function validateSecretDefinitions()
    {
        foreach ($this->getSecretDefinitions() as $secret_name => $secret_definition) {
            if (isset($secret_definition['bundle'])) {
                if (empty($secret_definition['bundle']) || !isset($secret_definition['key']) || empty($secret_definition['key'])) {
                    throw new \Exception("Bundle or key is not set for secret definition {$secret_name}");
                }
            }
        }
    }

    /**
     * Get Secrets for environment exporting.
     */
    public function getSecrets()
    {
        $secrets_provider_class = 'Kducharm\ProjectSettings\SecretsProviders\\' . $this->getSecretProviderClass();
        $secrets_provider = new $secrets_provider_class($this);
        echo $secrets_provider->getSecrets();
    }
    /**
     * Get Secrets for environment exporting.
     */
    public function checkSecretValues()
    {
        $secrets_provider_class = 'Kducharm\ProjectSettings\SecretsProviders\\' . $this->getSecretProviderClass();
        $secrets_provider = new $secrets_provider_class($this);
        $secrets_provider->checkSecretValues();
    }
}
