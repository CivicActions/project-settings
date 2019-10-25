<?php

namespace CivicActions\ProjectSettings\Constants;

use CivicActions\ProjectSettings\Utility\BasicEnum;

/**
 * Class ProjectEnvironmentTypes
 *
 * Available Environment Type constants.
 */
abstract class ProjectEnvironmentTypes extends BasicEnum
{
    const ENV_TYPE_LOCAL = 'local';
    const ENV_TYPE_DEV = 'dev';
    const ENV_TYPE_STAGE = 'stage';
    const ENV_TYPE_DEMO = 'demo';
    const ENV_TYPE_PROD = 'prod';
    const ENV_TYPE_CI = 'ci';
    const ENV_TYPE_QA = 'qa';
    const ENV_TYPE_TEST = 'test';
}
