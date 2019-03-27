<?php

namespace Kducharm\ProjectSettings\SecretsProviders;

/**
 * Retrieve secrets from environment variables.
 * @package Kducharm\ProjectSettings\SecretsProviders
 */
class EnvSecretsProvider extends SecretsProviderAbstract
{
    protected $providerName = 'env';

    /**
     * {@inheritdoc}
     */
    public function getSecretValue($secret_name, $bypass_prefix = false)
    {
        $secret_name_secondary = '';
        if (isset($this->secretsManager->getSecretDefinitions()[$secret_name])) {
            $secret_definition = $this->secretsManager->getSecretDefinitions()[$secret_name];
        } else {
            $secret_definition = [];
        }

        // Return the override value if set.
        if (isset($secret_definition['value'])) {
            return $secret_definition['value'];
        }

        if (isset($secret_definition['bundle'])) {
            $secret_name = $secret_definition['bundle'];
        }

        if (!$bypass_prefix) {
            $secret_name_primary = $this->secretsManager->getProjectPrefix() .
                $this->secretsManager->getEnvironmentPrefix() .
                $secret_name;
            $secret_name_secondary = $this->secretsManager->getProjectPrefix() .
                $secret_name;
        } else {
            $secret_name_primary = $secret_name;
        }

        // Attempt primary, then secondary secret name if specified.
        $secret = getenv($secret_name_primary);
        if (empty($secret)) {
            $secret = getenv($secret_name_secondary);
        }

        // Decode JSON if bundle/key specified in definition.
        if (!empty($secret) && isset($secret_definition['bundle'])) {
            $secret_decoded = json_decode($secret);
            $secret = $secret_decoded->{$secret_definition['key']};
        }

        return $secret;
    }
}
