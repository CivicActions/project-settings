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
    public function getSecretValue($secret_name, $hide_exceptions = false)
    {
        // Attempt primary, then secondary secret name if specified.
        for ($fallback = 0; $fallback < 3; $fallback++) {
            $secret_path = $this->getSecretPath($secret_name, $fallback);
            $secret = getenv($secret_path);

            if (!empty($secret)) {
                break;
            }
        }

        return $secret;
    }

    /**
     * Get Project Prefix (uppercase with _).
     * @return string
     */
    public function getProjectPrefix()
    {
        return strtoupper($this->secretsManager->getProjectName()) . '_';
    }

    /**
     * Get Environment Prefix (uppercase with _).
     * @return string
     *   Environment Prefix
     */
    public function getEnvironmentPrefix()
    {
        $env_prefix = $this->secretsManager->getProjectSettings()->getEnvironmentType();
        return strtoupper($env_prefix) . '_';
    }

    /**
     * {@inheritdoc}
     */
    public function getSecretPath($secret_name, $fallback = 0)
    {
        switch ($fallback) {
            case 0:
                $secret_path = $this->getProjectPrefix() . $this->getEnvironmentPrefix() . strtoupper($secret_name);
                break;
            case 1:
                $secret_path = $this->getProjectPrefix() . strtoupper($secret_name);
                break;
            default:
                $secret_path = strtoupper($secret_name);
                break;
        }
        return $secret_path;
    }
}
