<?php

namespace CivicActions\ProjectSettings\Constants;

use CivicActions\ProjectSettings\Utility\BasicEnumTrait;
use CivicActions\ProjectSettings\Utility\BasicEnumTraitInterface;

/**
 * Class ProjectEnvironmentTypes
 *
 * Available Environment Type constants.
 */
abstract class ProjectEnvironmentTypes implements BasicEnumTraitInterface
{
    use BasicEnumTrait;

    const ENV_TYPE_LOCAL = 'local';
    const ENV_TYPE_CI = 'ci';
    const ENV_TYPE_QA = 'qa';
    const ENV_TYPE_DEV = 'dev';
    const ENV_TYPE_TEST = 'test';
    const ENV_TYPE_STAGE = 'stage';
    const ENV_TYPE_PROD = 'prod';
}
