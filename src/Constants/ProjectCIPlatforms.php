<?php

namespace Kducharm\ProjectSettings\Constants;

use Kducharm\ProjectSettings\Utility\BasicEnum;

/**
 * Class ProjectCIPlatforms
 *
 * Available CI Platform constants.
 */
abstract class ProjectCIPlatforms extends BasicEnum
{
    const CI_PLATFORM_GITLAB = 'gitlab';
    const CI_PLATFORM_PIPELINES = 'pipelines';
    const CI_PLATFORM_PROBO = 'probo';
    const CI_PLATFORM_TRAVIS = 'travis';
    const CI_PLATFORM_TUGBOAT = 'tugboat';
}
