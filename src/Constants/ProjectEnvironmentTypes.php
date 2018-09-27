<?php

namespace Kducharm\ProjectSettings\Constants;

use Kducharm\ProjectSettings\Utility\BasicEnum;

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
    const ENV_TYPE_PROD = 'prod';
    const ENV_TYPE_CI = 'ci';
}
