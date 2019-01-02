<?php

namespace Kducharm\ProjectSettings;

/**
 * Class SampleSecretsManager
 * @package Kducharm\ProjectSettings
 */
class SampleSecretsManager extends SecretsManager
{

    // Project prefix name, with separator.
    protected $project_prefix = 'PROJECT_NAME_';

    // List of defined secrets per project
    protected $secret_definitions = [
        'DATABASE_PASSWORD',
    ];
}
