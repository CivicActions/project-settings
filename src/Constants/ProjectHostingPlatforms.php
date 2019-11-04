<?php

namespace CivicActions\ProjectSettings\Constants;

use CivicActions\ProjectSettings\Utility\BasicEnumTrait;
use CivicActions\ProjectSettings\Utility\BasicEnumTraitInterface;

/**
 * Class ProjectHostingPlatforms
 *
 * Available Hosting Platform constants.
 */
abstract class ProjectHostingPlatforms implements BasicEnumTraitInterface
{
    use BasicEnumTrait;

    const HOSTING_PLATFORM_ACQUIA = 'acquia';
    const HOSTING_PLATFORM_AWS = 'aws';
    const HOSTING_PLATFORM_DOCKER = 'docker';
    const HOSTING_PLATFORM_PANTHEON = 'pantheon';
}
