<?php

namespace Kducharm\ProjectSettings;

/**
 * Class SampleSecretsManager
 * @package Kducharm\ProjectSettings
 */
class SampleSecretsManager extends SecretsManager
{

    // Project prefix name, with separator.
    protected $project_name = 'PROJECT_NAME';

    // List of defined secrets per project.
    // Use of a 'bundle' will fetch via JSON by the 'key' specified.
    protected $secret_definitions = [
        'DATABASE_PASSWORD' => [
            'bundle' => 'DATABASE',
            'key' => 'password',
            'required' => false,
        ],
        'NO_JSON_PASSWORD' => [
        ],
        'API_PASSWORD' => [
            'secrets_provider_class' => 'EnvSecretsProvider',
        ],
        // For AWS usage, a region comes from environment only and is required to be set, it can be prefixed.
        'AWS_DEFAULT_REGION' => [
            // Value will override the secret's value.
            'value' => 'us-east-2',
            'secrets_provider_class' => 'EnvSecretsProvider',
            // Can be set to not be required so checking valid secrets passes.
            'required' => false,
        ],
    ];
}
