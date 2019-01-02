<?php

namespace Kducharm\ProjectSettings\Tests\Command;

use Kducharm\ProjectSettings\Constants\ProjectEnvironmentTypes;
use Kducharm\ProjectSettings\ProjectSettings;
use Kducharm\ProjectSettings\SampleSecretsManager;

class ProjectSettingsTest extends \PHPUnit_Framework_TestCase
{

    /** @var ProjectSettings */
    private $projectSettings;

    /** @var SampleSecretsManager */
    private $secretsManager;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();
        $this->projectSettings = new ProjectSettings('environments');
        try {
            $this->secretsManager = new SampleSecretsManager($this->projectSettings);
        } catch (\Exception $e) {
            $this->throwException($e);
        }
    }

    /**
     * Tests that default environment type is local.
     */
    public function testDefaultEnvironmentType()
    {
        $env_type = $this->projectSettings->getEnvironmentType();
        if ($this->projectSettings->detectedEnvironmentType()) {
            $this->assertEquals(ProjectEnvironmentTypes::ENV_TYPE_CI, $env_type);
        } else {
            $this->assertEquals(ProjectEnvironmentTypes::ENV_TYPE_LOCAL, $env_type);
        }
    }

    /**
     * Tests secrets manager.
     */
    public function testSecretManager()
    {
        // Check for non-existent secret
        $secret = $this->secretsManager->getSecret('DATABASE_PASSWORD');
        $this->assertNull($secret);

        // Check fallback non-environment secret.
        $project_prefix = $this->secretsManager->getProjectPrefix();
        $this->assertNotEmpty($project_prefix);
        putenv($project_prefix . 'DATABASE_PASSWORD=abc123');
        $secret = $this->secretsManager->getSecret('DATABASE_PASSWORD');
        $this->assertEquals('abc123', $secret);

        // Check environment specific secret.
        $env_prefix = $this->secretsManager->getEnvironmentPrefix();
        $this->assertNotEmpty($env_prefix);
        putenv($project_prefix . $env_prefix . 'DATABASE_PASSWORD=def456');
        $secret = $this->secretsManager->getSecret('DATABASE_PASSWORD');
        $this->assertEquals('def456', $secret);
    }
}
