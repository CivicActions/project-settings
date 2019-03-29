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
        $secret_definitions = $this->secretsManager->getSecretDefinitions();
        $bundles = array_column($secret_definitions, 'bundle');
        $bundles = array_unique($bundles);

        foreach ($bundles as $bundle) {
            $secret_value = $this->getSecretValue($bundle);
            if (empty($secret_value)) {
                throw new \Exception("No secret value found for '{$bundle}'.");
            }
            $secret_decoded = json_decode($secret_value, true);
            foreach ($secret_definitions as $name => $definition) {
                if (isset($definition['bundle']) && $definition['bundle'] == $bundle) {
                    $required = true;
                    if (isset($definition['required']) && $definition['required'] == false) {
                        $required = false;
                    }
                    if (!isset($secret_decoded[$definition['key']]) && $required) {
                        $secret_path = $this->secretsManager->env_secrets_provider->getSecretPath($name);
                        throw new \Exception("No secret value found for '{$name}' in bundle '{$bundle}', path '{$secret_path}'.");
                    }
                }
            }
        }
    }
}
