<?php

namespace Kducharm\ProjectSettings\Tests\Command;

use Kducharm\ProjectSettings\Constants\ProjectEnvironmentTypes;
use Kducharm\ProjectSettings\ProjectSettings;

class ProjectSettingsTest extends \PHPUnit_Framework_TestCase
{

    /** @var ProjectSettings */
    private $projectSettings;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();
        $this->projectSettings = new ProjectSettings();
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
}
