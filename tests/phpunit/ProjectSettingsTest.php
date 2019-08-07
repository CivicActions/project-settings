<?php

namespace Kducharm\ProjectSettings\Tests\Command;

use Kducharm\ProjectSettings\Constants\ProjectEnvironmentTypes;
use Kducharm\ProjectSettings\ProjectSettings;
use Kducharm\ProjectSettings\SampleSecretsManager;
use Kducharm\ProjectSettings\SecretsProviders\SecretsProviderAbstract;

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
            $this->secretsManager->setSecretsProviderClass('EnvSecretsProvider');
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
        $project_prefix = $this->secretsManager->getSecretsProvider()->getProjectPrefix();

        // Check for non-existent secret.
        $exceptionCaught = false;
        try {
            $secret = $this->secretsManager->getSecret('DOESNT_EXIST');
        } catch (\Exception $e) {
            $exceptionCaught = true;
        }
        $this->assertTrue($exceptionCaught, 'Secret not existing did not catch exception.');

        // Check for non-defined secret
        putenv($project_prefix . 'DATABASE');
        $secret = $this->secretsManager->getSecret('DATABASE_PASSWORD');
        $this->assertEmpty($secret, 'Secret value was not empty.');

        // Check fallback non-environment secret.
        $this->assertNotEmpty($project_prefix);
        $db_secret = ['password' => 'abc123'];
        putenv($project_prefix . 'DATABASE=' . json_encode($db_secret));
        $secret = $this->secretsManager->getSecret('DATABASE_PASSWORD');
        $this->assertEquals('abc123', $secret);

        // Check environment specific secret.
        $env_prefix = $this->secretsManager->getSecretsProvider()->getEnvironmentPrefix();
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
        $this->assertEmpty($secret, 'Secret value was not empty.');
        putenv('API_PASSWORD=ghi789');
        $secret = $this->secretsManager->getSecret('API_PASSWORD', true);
        $this->assertEquals('ghi789', $secret);

        // Check bundle json exists.
        $secrets = $this->secretsManager->getSecretsProvider()->getSecrets();
        $bundle_found = (strpos($secrets, $project_prefix . $env_prefix . 'DATABASE=') !== false);
        $this->assertTrue($bundle_found, 'Bundle ' . $secrets, $project_prefix . $env_prefix . 'DATABASE not found in exports.');

        // Check non-bundle secrets export exists.
        $secrets = $this->secretsManager->getSecretsProvider()->getSecrets();
        $bundle_found = (strpos($secrets, $project_prefix . $env_prefix . 'NO_JSON_PASSWORD=') !== false);
        $this->assertTrue($bundle_found, 'Non-Bundle ' . $secrets, $project_prefix . $env_prefix . 'NO_JSON_PASSWORD not found in exports.');
    }

    /**
     * Test Env Export Escaping.
     */
    public function testEnvExportEscape()
    {
        // Generate a shell export line for ascii char set.
        $ascii_string = self::generateAsciiChars();
        $ascii_export_test_cmd = "export TEST_ASCII_CHARS='" .
            SecretsProviderAbstract::escapeVar($ascii_string) . "' && echo \"\$TEST_ASCII_CHARS\"";

        // Read output back from shell escape.
        exec($ascii_export_test_cmd, $compare_ascii_string_array);

        $this->assertEquals($compare_ascii_string_array[0], $ascii_string);
    }

    /**
     * Generate ASCII Chars for testing.
     */
    private static function generateAsciiChars()
    {
        $output = '';
        for ($i = 32; $i < 127; $i++) {
            $output .= mb_convert_encoding(chr($i), 'UTF-8', 'ISO-8859-1');
        }
        for ($i = 128; $i < 255; $i++) {
            $output .= mb_convert_encoding(chr($i), 'UTF-8', 'ISO-8859-1');
        }
        return $output;
    }
}
