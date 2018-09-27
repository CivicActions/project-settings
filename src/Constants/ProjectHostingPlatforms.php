<?php

namespace Kducharm\ProjectSettings\Constants;

use Kducharm\ProjectSettings\Utility\BasicEnum;

/**
 * Class ProjectHostingPlatforms
 *
 * Available Hosting Platform constants.
 */
abstract class ProjectHostingPlatforms extends BasicEnum
{
    const HOSTING_PLATFORM_ACQUIA = 'acquia';
    const HOSTING_PLATFORM_AWS = 'aws';
    const HOSTING_PLATFORM_DOCKER = 'docker';
    const HOSTING_PLATFORM_PANTHEON = 'pantheon';
}
