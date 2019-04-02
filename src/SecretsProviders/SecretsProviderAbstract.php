<?php

namespace Kducharm\ProjectSettings\SecretsProviders;

use Kducharm\ProjectSettings\SecretsManager;

/**
 * Class SecretsProviderAbstract
 * @package SecretsProviders
 */
abstract class SecretsProviderAbstract
{
    protected $secretsManager;

    /**
     * SecretsProviderAbstract constructor.
     *
     * @param SecretsManager $secrets_manager
     *   Secrets manager object.
     */
    public function __construct(SecretsManager $secrets_manager)
    {
        $this->secretsManager = $secrets_manager;
    }

    /**
     * Get Secret Value from Secrets Provider.
     *
     * @param $secret_name
     *   Secret Name.
     * @return mixed
     *   Secret Value.
     */
    abstract public function getSecretValue($secret_name);

    /**
     * Get Project Prefix.
     * @return string
     *   Project prefix
     */
    abstract public function getProjectPrefix();

    /**
     * Get Environment Prefix.
     * @return string
     *   Environment Prefix
     */
    abstract public function getEnvironmentPrefix();

    /**
     * Get Secret Path.
     * @param $secret_name
     *   Secret Name.
     * @param int $fallback
     *   Attempt different levels of fallback.
     * @return string
     *   Secret path.
     */
    abstract public function getSecretPath($secret_name, $fallback = 0);


    /**
     * Get Secrets for environment exporting.
     */
    public function getSecrets()
    {
        $secret_definitions = $this->secretsManager->getSecretDefinitions();
        $bundles = array_column($secret_definitions, 'bundle');
        $bundles = array_unique($bundles);

        foreach ($bundles as $bundle) {
            $secret_value = $this->getSecretValue($bundle);
            $secret_decoded = json_decode($secret_value, true);
            foreach ($secret_definitions as $name => $definition) {
                if (isset($definition['bundle']) && $definition['bundle'] == $bundle) {
                    if (isset($secret_decoded[$definition['key']])) {
                        $secret_path = $this->secretsManager->env_secrets_provider->getSecretPath($name);
                        echo "export {$secret_path}=\"{$secret_decoded[$definition['key']]}\"\n";
                    }
                }
            }
        }
    }

    /**
     * Check that all defined secrets have values.
     */
    public function checkSecretValues()
    {
        $bundles = [];
        $secret_definitions = $this->secretsManager->getSecretDefinitions();
        foreach ($secret_definitions as $name => $secret_definition) {
            $secret_name_key = ['name' => $name];
            if (isset($secret_definition['key'])) {
                $secret_name_key += ['key' => $secret_definition['key']];
            }
            if (isset($secret_definition['required'])) {
                $secret_name_key += ['required' => $secret_definition['required']];
            }

            if (isset($secret_definition['bundle'])) {
                $index_key = $secret_definition['bundle'];
                $bundles[$index_key]['type'] = 'json';
            } else {
                $index_key = $name;
            }
            $bundles[$index_key]['secrets'][] = $secret_name_key;
            if (isset($secret_definition['required'])) {
                $bundles[$index_key]['required'] = $secret_definition['required'];
            }
        }

        // Validate secrets that are in bundles.
        foreach ($bundles as $bundle => $bundle_definition) {
            $is_json = (isset($bundle_definition['type']) && $bundle_definition['type'] == 'json');
            $required = false;
            // If any one secret in a bundle is required, then the whole bundle must exist.
            foreach ($bundle_definition['secrets'] as $secret_settings) {
                $required |= !isset($secret_settings['required']) ? true : $secret_settings['required'];
            }
            if ($required) {
                $secret_value = $this->getSecretValue($bundle);
                if (empty($secret_value)) {
                    throw new \Exception("No secret value found for '{$bundle}'.");
                }

                // Check that all required secrets inside a bundle exist.
                if ($is_json) {
                    $secret_decoded = json_decode($secret_value, true);
                    foreach ($bundle_definition['secrets'] as $secret_settings) {
                        $key_required = !isset($secret_settings['required']) ? true : $secret_settings['required'];

                        if (!isset($secret_decoded[$secret_settings['key']]) && $key_required) {
                            $secret_path = $this->secretsManager->env_secrets_provider->getSecretPath($secret_settings['name']);
                            throw new \Exception("No secret value found for '{$secret_settings['name']}' in bundle '{$bundle}', path '{$secret_path}'.");
                        }
                    }
                }
            }
        }
    }
}
