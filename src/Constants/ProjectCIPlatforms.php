<?php

namespace CivicActions\ProjectSettings\Constants;

use CivicActions\ProjectSettings\Utility\BasicEnumTrait;
use CivicActions\ProjectSettings\Utility\BasicEnumTraitInterface;

/**
 * Class ProjectCIPlatforms
 *
 * Available CI Platform constants.
 */
abstract class ProjectCIPlatforms implements BasicEnumTraitInterface
{
    use BasicEnumTrait;

    const CI_PLATFORM_GITLAB = 'gitlab';
    const CI_PLATFORM_PIPELINES = 'pipelines';
    const CI_PLATFORM_PROBO = 'probo';
    const CI_PLATFORM_TRAVIS = 'travis';
    const CI_PLATFORM_TUGBOAT = 'tugboat';
}
