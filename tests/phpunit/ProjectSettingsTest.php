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
            $this->secretsManager->setSecretProviderClass('EnvSecretsProvider');
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
        $project_prefix = $this->secretsManager->getProjectPrefix();

        // Check for non-existent secret
        putenv($project_prefix . 'DATABASE');
        $secret = $this->secretsManager->getSecret('DATABASE_PASSWORD');
        $this->assertEmpty($secret);

        // Check fallback non-environment secret.
        $this->assertNotEmpty($project_prefix);
        $db_secret = ['password' => 'abc123'];
        putenv($project_prefix . 'DATABASE=' . json_encode($db_secret));
        $secret = $this->secretsManager->getSecret('DATABASE_PASSWORD');
        $this->assertEquals('abc123', $secret);

        // Check environment specific secret.
        $env_prefix = $this->secretsManager->getEnvironmentPrefix();
        $this->assertNotEmpty($env_prefix);
        $db_secret = ['password' => 'def456'];
        putenv($project_prefix . $env_prefix . 'DATABASE=' . json_encode($db_secret));
        $secret = $this->secretsManager->getSecret('DATABASE_PASSWORD');
        $this->assertEquals('def456', $secret);

        // Check non-JSON encoded data.
        putenv($project_prefix . 'NO_JSON_PASSWORD=jkl012');
        $secret = $this->secretsManager->getSecret('NO_JSON_PASSWORD');
        $this->assertEquals('jkl012', $secret);

        // Check bypass prefix.
        $secret = $this->secretsManager->getSecret('API_PASSWORD', true);
        $this->assertEmpty($secret);
        putenv('API_PASSWORD=ghi789');
        $secret = $this->secretsManager->getSecret('API_PASSWORD', true);
        $this->assertEquals('ghi789', $secret);
    }
}
