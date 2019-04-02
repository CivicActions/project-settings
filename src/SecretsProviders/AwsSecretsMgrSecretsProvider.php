<?php

namespace Kducharm\ProjectSettings\SecretsProviders;

use Aws\SecretsManager\Exception\SecretsManagerException;
use Aws\SecretsManager\SecretsManagerClient;

/**
 * Class AwsSecretsMgrSecretsProvider
 * @package Kducharm\ProjectSettings\SecretsProviders
 */
class AwsSecretsMgrSecretsProvider extends SecretsProviderAbstract
{
    /**
     * {@inheritdoc}
     */
    public function getSecretValue($secret_name)
    {
        // Retrieve region from environment.
        $envSecretsProvider = new EnvSecretsProvider($this->secretsManager);
        $secret_definitions = $this->secretsManager->getSecretDefinitions();
        if (isset($secret_definitions['AWS_DEFAULT_REGION']) &&
            isset($secret_definitions['AWS_DEFAULT_REGION']['value'])) {
            $region = $secret_definitions['AWS_DEFAULT_REGION']['value'];
        } else {
            $region = $envSecretsProvider->getSecretValue('AWS_DEFAULT_REGION');
        }

        if (empty($region)) {
            throw new \Exception("Could not retrieve AWS_DEFAULT_REGION from environment");
        }

        $client = new SecretsManagerClient([
            'version' => '2017-10-17',
            'region' => $region,
        ]);

        try {
            $secret_path = $this->getSecretPath($secret_name);
            $result = $client->getSecretValue(['SecretId' => $secret_path]);
        } catch (SecretsManagerException $exception) {
            // If not found, try without env prefix.
            if ($exception->getStatusCode() == 400) {
                $alt_secret_path = $this->getSecretPath($secret_name, 1);
                try {
                    $result = $client->getSecretValue(['SecretId' => $alt_secret_path]);
                } catch (SecretsManagerException $exception) {
                    echo "Could not load secret from AWS Secrets Manager - path(s) '{$secret_path}' '{$alt_secret_path}': " . $exception->getMessage();
                    exit(1);
                }
            } else {
                echo "Could not load secret from AWS Secrets Manager - path '{$secret_path}': " . $exception->getMessage();
                exit(1);
            }
        }

        if (isset($result['SecretString'])) {
            $secret = $result['SecretString'];
        } else {
            $secret = base64_decode($result['SecretBinary']);
        }
        return $secret;
    }

    /**
     * Get Project Prefix (lowercase with /).
     * @return string
     */
    public function getProjectPrefix()
    {
        return strtolower($this->secretsManager->getProjectName()) . '/';
    }

    /**
     * Get Environment Prefix (lowercase with /).
     * @return string
     *   Environment Prefix
     */
    public function getEnvironmentPrefix()
    {
        $env_prefix = $this->secretsManager->getProjectSettings()->getEnvironmentType();
        return strtolower($env_prefix) . '/';
    }

    /**
     * {@inheritdoc}
     */
    public function getSecretPath($secret_name, $fallback = 0)
    {
        switch ($fallback) {
            case 0:
                $secret_path = $this->getEnvironmentPrefix() . $this->getProjectPrefix() . strtolower($secret_name);
                break;
            case 1:
                $secret_path = $this->getProjectPrefix() . strtolower($secret_name);
                break;
            default:
                $secret_path = strtolower($secret_name);
                break;
        }

        return $secret_path;
    }
}
