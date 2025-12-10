<?php

namespace CivicActions\ProjectSettings\SecretsProviders;

use CivicActions\ProjectSettings\SecretsManager;

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
     * @param $hide_exceptions
     *   If TRUE, will not throw exceptions/exit.
     * @return mixed
     *   Secret Value.
     */
    abstract public function getSecretValue($secret_name, $hide_exceptions = false);

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
        $output = '';
        $secret_definitions = $this->secretsManager->getSecretDefinitions();

        // Output non-bundles who are not env secrets provided.
        foreach ($secret_definitions as $key => $secret_def) {
            if (!isset($secret_def['bundle'])) {
                if (!isset($secret_def['secrets_provider_class']) ||
                    (isset($secret_def['secrets_provider_class']) && $secret_def['secrets_provider_class'] !== 'EnvSecretsProvider')) {
                    try {
                        $secret_value = $this->getSecretValue($key);
                        $secret_path = $this->secretsManager->getSecretsProvider('EnvSecretsProvider')->getSecretPath($key);
                        $output .= "export {$secret_path}='" . self::escapeVar($secret_value) . "'\n";
                    } catch (\Exception $e) {
                        // @todo Does not throw any errors so it doesn't corrupt export output.
                        fwrite(STDERR, $e->getMessage() . "\nSecret: {$key}\n");
                    }
                }
            }
        }

        $bundles = array_column($secret_definitions, 'bundle');
        $bundles = array_unique($bundles);

        foreach ($bundles as $bundle) {
            try {
                $secret_value = $this->getSecretValue($bundle, true);
                $secret_decoded = json_decode($secret_value ?? '', true);
                // Export bundle as well as individual values.
                $secret_path = $this->secretsManager->getSecretsProvider('EnvSecretsProvider')->getSecretPath($bundle);
                // Escape bash
                $output .= "export {$secret_path}='" . self::escapeVar($secret_value) . "'\n";

                foreach ($secret_definitions as $name => $definition) {
                    if (isset($definition['bundle']) && $definition['bundle'] == $bundle) {
                        if (isset($secret_decoded[$definition['key']])) {
                            $decodedSecretDefinitionKey = $secret_decoded[$definition['key']];
                            $secret_path = $this->secretsManager->getSecretsProvider('EnvSecretsProvider')->getSecretPath($name);
                            if (is_array($decodedSecretDefinitionKey)) {
                                $output .= "export {$secret_path}='" . self::escapeVar($secret_value) . "'\n";
                            } elseif (is_string($decodedSecretDefinitionKey)) {
                                $output .= "export {$secret_path}='" . self::escapeVar($decodedSecretDefinitionKey) . "'\n";
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                // @todo Does not throw any errors so it doesn't corrupt export output.
                fwrite(STDERR, $e->getMessage() . "\nSecret: {$bundle}\n");
            }
        }
        return $output;
    }

    /**
     * Escape variable.
     *
     * @param $var
     * @return mixed|string
     */
    public static function escapeVar($var)
    {
        if (empty($var)) {
            return null;
        }
        return str_replace("'", "'\\''", $var);
    }
}
