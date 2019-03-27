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
    public function getSecretValue($secret_name, $bypass_prefix = false)
    {
        $secret_definition = $this->secretsManager->getSecretDefinitions()[$secret_name];

        // Return the override value if set.
        if (isset($secret_definition['value'])) {
            return $secret_definition['value'];
        }

        // Retrieve region from environment using project_prefix_env_AWS_REGION, AWS_REGION, then a default.
        $envSecretsProvider = new EnvSecretsProvider($this->secretsManager);
        $region = $envSecretsProvider->getSecretValue('AWS_DEFAULT_REGION');

        // Attempt without project/env prefixing if empty.
        if (empty($region)) {
            $region = $envSecretsProvider->getSecretValue('AWS_DEFAULT_REGION', true);
        }

        $client = new SecretsManagerClient([
            'version' => '2017-10-17',
            'region' => $region,
        ]);


        // If bundle is set, use that as the secret source.
        $secret_name_secondary = '';

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

        try {
            $result = $client->getSecretValue(['SecretId' => $secret_name_primary]);
        } catch (SecretsManagerException $exception) {
            // If not found, try without env prefix.
            if (!empty($secret_name_secondary)) {
                if ($exception->getStatusCode() == 400) {
                    $result = $client->getSecretValue(['SecretId' => $secret_name_secondary]);
                } else {
                    throw $exception;
                };
            } else {
                throw $exception;
            }
        }

        if (isset($result['SecretString'])) {
            // @todo Returns whole undecoded JSON string if no bundle specified, not sure if that is useful.
            $secret = $result['SecretString'];
            if (isset($secret_definition['bundle'])) {
                // Check if JSON decoding is necessary by json_key definition.
                $secret_decoded = json_decode($secret);
                $secret = $secret_decoded->{$secret_definition['key']};
            }
        } else {
            $secret = base64_decode($result['SecretBinary']);
        }

        return $secret;
    }
}
