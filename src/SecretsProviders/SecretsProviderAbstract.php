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
     * @param bool $bypass_prefix
     *   If TRUE, bypasses PROJECT_NAME and ENV prefixes.
     * @return mixed
     *   Secret Value.
     */
    abstract public function getSecretValue($secret_name, $bypass_prefix = false);
}
