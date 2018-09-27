<?php

namespace Kducharm\ProjectSettings;

interface ProjectSettingsInterface
{
    /**
     * Set CI Platform.
     *
     * @param mixed $platform
     *   CIPlatforms constant.
     *
     * @return bool
     *   FALSE if invalid platform specified.
     */
    public function setCIPlatform($platform);

    /**
     * Get CI Platform.
     * @return mixed
     *   CIPlatforms constant
     */
    public function getCIPlatform();

    /**
     * Set Hosting Platform.
     *
     * @param mixed $platform
     *   HostingPlatforms constant.
     *
     * @return bool
     *   FALSE if invalid platform.
     */
    public function setHostingPlatform($platform);

    /**
     * Get Hosting Platform.
     * @return mixed
     *   HostingsPlatforms constant
     */
    public function getHostingPlatform();

    /**
     * Set Hosting Platform.
     *
     * @param mixed $env_type
     *   EnvironmentTypes constant.
     *
     * @return bool
     *   FALSE if invalid platform.
     */
    public function setEnvironmentType($env_type);

    /**
     * Get Hosting Platform.
     * @return mixed
     *   HostingsPlatforms constant
     */
    public function getEnvironmentType();

    /**
     * Get Loaded Settings Files.
     * @return array
     *   Array of files loaded.
     */
    public function getSettingsFiles();

    /**
     * Add Loaded Settings File.
     *
     * @param string $path_to_file
     *   Path to file being loaded.
     */
    public function addSettingsFiles($path_to_file);

    /**
     * Outputs shell exports for platform/environments.
     *
     * Intended to be run via '. bin/init_project_settings.sh'
     */
    public function printShellExports();

    /**
     * Sets local environment variables.
     */
    public function setEnvVariables();
}
