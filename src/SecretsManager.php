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
    protected $secrets_provider_class = 'EnvSecretsProvider';

    public $secrets_providers;

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
            $this->setSecretsProviderClass($secrets_provider_class_override);
        }
        $this->secrets_providers['EnvSecretsProvider'] = new EnvSecretsProvider($this);

        $this->valid_secret_names = $this->getValidSecrets($this->getProjectSettings()->getEnvironmentType());
    }

    /**
     * @return array|false|string
     */
    public function getSecretsProviderClass()
    {
        return $this->secrets_provider_class;
    }

    /**
     * @param array|false|string $secrets_provider_class
     */
    public function setSecretsProviderClass($secrets_provider_class)
    {
        $this->secrets_provider_class = $secrets_provider_class;
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

            if (isset($secret_definition['secrets_provider_class'])) {
                $secrets_provider = $this->getSecretsProvider($secret_definition['secrets_provider_class']);
            } else {
                $secrets_provider = $this->getSecretsProvider();
            }

            // Check if this part of a bundle.
            if (isset($secret_definition['bundle'])) {
                $secret_bundle = $secrets_provider->getSecretValue($secret_definition['bundle']);
                $secret_decoded = json_decode($secret_bundle, true);
                $secret_value = isset($secret_decoded[$secret_definition['key']]) ? $secret_decoded[$secret_definition['key']] : null;
            } else {
                $secret_value = $secrets_provider->getSecretValue($secret_name);
            }
            if (is_array($secret_value)) {
                return json_encode($secret_value);
            }
            return $secret_value;
        } else {
            throw new \Exception("Not a valid secret name (undefined in secret definitions).");
        }
    }

   /**
     * Get Valid Secrets
     * @return array
     *   Array of Secret names
     * @throws \Exception
     */
    public function getValidSecrets()
    {
        $valid_secret_names = [];

        foreach ($this->getSecretDefinitions() as $secret_name => $secret_definition) {
            $valid_secret_names[] = $this->getSecretsProvider('EnvSecretsProvider')->getSecretPath($secret_name);
            $valid_secret_names[] = $this->getSecretsProvider('EnvSecretsProvider')->getSecretPath($secret_name, 1);
        }

        // Allow querying of bundle names.
        $bundles = array_unique(array_column($this->getSecretDefinitions(), 'bundle'));

        foreach ($bundles as $bundle) {
            $valid_secret_names[] = $this->getSecretsProvider('EnvSecretsProvider')->getSecretPath($bundle);
            $valid_secret_names[] = $this->getSecretsProvider('EnvSecretsProvider')->getSecretPath($bundle, 1);
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
        echo $this->getSecretsProvider()->getSecrets();
    }

    /**
     * Get Secrets Provider object.
     *
     * @param string $secrets_provider_class
     *   Class name w/o namespace, or if NULL, uses getSecretProviderClass.
     *
     * @return object
     *   SecretsProvider object.
     *
     * @throws \Exception
     */
    public function getSecretsProvider($secrets_provider_class = null)
    {
        if (empty($secrets_provider_class)) {
            $secrets_provider_class = $this->getSecretsProviderClass();
        }

        $secrets_provider_class_name = 'Kducharm\ProjectSettings\SecretsProviders\\' . $secrets_provider_class;
        // Validate secrets provider class exists.
        if (!class_exists($secrets_provider_class_name)) {
            throw new \Exception("{$secrets_provider_class_name} Secrets Provider Class does not exist!");
        }

        if (!isset($this->secrets_providers[$secrets_provider_class_name])) {
            $this->secrets_providers[$secrets_provider_class_name] = new $secrets_provider_class_name($this);
        }
        return $this->secrets_providers[$secrets_provider_class_name];
    }

    /**
     * Check that all defined secrets have values.
     */
    public function checkSecretValues()
    {
        // Validate secrets that are in bundles.
        $bundles = $this->getSecretBundleDefinitions();
        foreach ($bundles as $bundle => $bundle_definition) {
            $is_json = (isset($bundle_definition['type']) && $bundle_definition['type'] == 'json');
            $required = false;
            // If any one secret in a bundle is required, then the whole bundle must exist.
            foreach ($bundle_definition['secrets'] as $secret_settings) {
                $required |= !isset($secret_settings['required']) ? true : $secret_settings['required'];
            }
            if ($required) {
                if (isset($secret_settings['secrets_provider_class'])) {
                    $secrets_provider = $this->getSecretsProvider($secret_settings['secrets_provider_class']);
                } else {
                    $secrets_provider = $this->getSecretsProvider();
                }
                $secret_value = $secrets_provider->getSecretValue($bundle);
                if (empty($secret_value)) {
                    throw new \Exception("No secret value found for '{$bundle}'.");
                }

                // Check that all required secrets inside a bundle exist.
                if ($is_json) {
                    $secret_decoded = json_decode($secret_value, true);
                    foreach ($bundle_definition['secrets'] as $secret_settings) {
                        $key_required = !isset($secret_settings['required']) ? true : $secret_settings['required'];
                        if (!isset($secret_decoded[$secret_settings['key']]) && $key_required) {
                            $secret_path = $secrets_provider->getSecretPath($secret_settings['name']);
                            throw new \Exception("No secret value found for '{$secret_settings['key']}' in bundle '{$bundle}', path '{$secret_path}'.");
                        }
                    }
                }
            }
        }
    }

    /**
     * Get Secret Definitions by Bundle.
     *
     * @return array
     *   Array of definitions keyed by bundle name, or secret name if no bundle..
     */
    protected function getSecretBundleDefinitions()
    {
        $bundles = [];
        $secret_definitions = $this->getSecretDefinitions();
        foreach ($secret_definitions as $name => $secret_definition) {
            $secret_name_definition = ['name' => $name];
            $secret_name_definition += $secret_definition;

            if (isset($secret_definition['bundle'])) {
                $index_key = $secret_definition['bundle'];
                $bundles[$index_key]['type'] = 'json';
            } else {
                $index_key = $name;
            }
            $bundles[$index_key]['secrets'][] = $secret_name_definition;
        }

        return $bundles;
    }
}
