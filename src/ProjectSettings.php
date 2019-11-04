<?php

namespace CivicActions\ProjectSettings;

/**
 * Class ProjectSettings
 */
class ProjectSettings implements ProjectSettingsInterface
{
    // Environment variables that can override hosting/ci platforms and env type.
    const PROJECT_HOSTING_PLATFORM_ENV_OVERRIDE = 'PROJECT_HOSTING_PLATFORM';
    const PROJECT_HOSTING_PLATFORMS_CLASS_ENV_OVERRIDE = 'PROJECT_HOSTING_PLATFORMS_CLASS';
    const PROJECT_CI_PLATFORM_ENV_OVERRIDE = 'PROJECT_CI_PLATFORM';
    const PROJECT_CI_PLATFORMS_CLASS_ENV_OVERRIDE = 'PROJECT_CI_PLATFORMS_CLASS';
    const PROJECT_ENVIRONMENT_TYPE_ENV_OVERRIDE = 'PROJECT_SERVER_ENVIRONMENT';
    const PROJECT_ENVIRONMENT_TYPES_CLASS_ENV_OVERRIDE = 'PROJECT_ENVIRONMENT_TYPES_CLASS';
    const PROJECT_SETTINGS_ENV_FOLDER_OVERRIDE = 'PROJECT_SETTINGS_ENV_FOLDER';

    private $hostingPlatform;
    private $hostingPlatformsClass = '\CivicActions\ProjectSettings\Constants\ProjectHostingPlatforms';
    private $ciPlatform;
    private $ciPlatformsClass = '\CivicActions\ProjectSettings\Constants\ProjectCIPlatforms';
    private $environmentType;
    private $environmentTypesClass = '\CivicActions\ProjectSettings\Constants\ProjectEnvironmentTypes';

    private $detected_hosting_platform = false;
    private $detected_ci_platform = false;
    private $detected_environment_type = false;
    private $settingsFiles = [];
    private $settings_root_dir;

    /**
     * ProjectSettings constructor.
     *
     * @param string $settings_dir
     *   If set, will look for environments folder in specified directory.
     * @param string $environmentTypesClass
     *   Environment Types Class override.
     * @param string $hostingPlatformsClass
     *   Hosting Platforms Types Class override.
     * @param string $ciPlatformsClass
     *   CI Platforms override.
     * @throws \Exception
     */
    public function __construct(
        $settings_dir = '',
        $environmentTypesClass = '',
        $hostingPlatformsClass = '',
        $ciPlatformsClass = ''
    ) {
        if (!empty($settings_dir)) {
            $this->settings_root_dir = rtrim($settings_dir, '/');
        } elseif ($settings_dir = rtrim(getenv(self::PROJECT_SETTINGS_ENV_FOLDER_OVERRIDE))) {
            $this->settings_root_dir = rtrim($settings_dir, '/');
        } else {
            $this->settings_root_dir = dirname(__FILE__);
        }

        $this->loadCustomClasses();

        if (!empty($environmentTypesClass)) {
            $this->setEnvironmentTypesClass($environmentTypesClass);
        } elseif ($env_types_class = rtrim(getenv(self::PROJECT_ENVIRONMENT_TYPES_CLASS_ENV_OVERRIDE))) {
            $this->setEnvironmentTypesClass($env_types_class);
        }

        if (!empty($hostingPlatformsClass)) {
            $this->setHostingPlatformsClass($hostingPlatformsClass);
        } elseif ($hosting_platforms_class = rtrim(getenv(self::PROJECT_HOSTING_PLATFORMS_CLASS_ENV_OVERRIDE))) {
            $this->setHostingPlatformsClass($hosting_platforms_class);
        }

        if (!empty($ciPlatformsClass)) {
            $this->setCIPlatformsClass($ciPlatformsClass);
        } elseif ($ci_platforms_class = rtrim(getenv(self::PROJECT_CI_PLATFORMS_CLASS_ENV_OVERRIDE))) {
            $this->setCIPlatformsClass($ci_platforms_class);
        }

        $this->detected_hosting_platform = $this->determineHostingPlatform();
        $this->detected_ci_platform = $this->determineCIPlatform();
        $this->detected_environment_type = $this->determineEnvironmentType();
        $this->setEnvVariables();
        $this->scanSettingsFiles();
    }

    /**
     * {@inheritDoc}
     */
    public function getEnvironmentTypesClass()
    {
        return $this->environmentTypesClass;
    }

    /**
     * {@inheritDoc}
     */
    public function setEnvironmentTypesClass($environmentTypesClass)
    {
        // Check class exists.
        if (!class_exists($environmentTypesClass)) {
            throw new \Exception("Missing environmentTypesClass " . $environmentTypesClass);
        }

        $this->environmentTypesClass = $environmentTypesClass;
    }

    /**
     * {@inheritDoc}
     */
    public function getHostingPlatformsClass()
    {
        return $this->hostingPlatformsClass;
    }

    /**
     * {@inheritDoc}
     */
    public function setHostingPlatformsClass($hostingPlatformsClass)
    {
        // Check class exists.
        if (!class_exists($hostingPlatformsClass)) {
            throw new \Exception("Missing environmentTypesClass " . $hostingPlatformsClass);
        }
        $this->hostingPlatformsClass = $hostingPlatformsClass;
    }

    /**
     * {@inheritDoc}
     */
    public function getCIPlatformsClass()
    {
        return $this->ciPlatformsClass;
    }

    /**
     * {@inheritDoc}
     */
    public function setCIPlatformsClass($ciPlatformsClass)
    {
        $this->ciPlatformsClass = $ciPlatformsClass;
    }

    /**
     * Determine CI Platform based on environment variables or other criteria.
     * @return bool
     *   true if a platform was detected.
     */
    protected function determineCIPlatform()
    {
        $ci_platforms_class = $this->getCIPlatformsClass();
        if ($ci_platform = getenv(self::PROJECT_CI_PLATFORM_ENV_OVERRIDE)) {
            if ($ci_platforms_class::isValidValue($ci_platform, true)) {
                $this->setCIPlatform($ci_platform);
                return true;
            }
        }

        if (getenv('TRAVIS') !== false) {
            $this->setCIPlatform($ci_platforms_class::CI_PLATFORM_TRAVIS);
            return true;
        }

        if (getenv('PIPELINE_ENV') !== false) {
            $this->setCIPlatform($ci_platforms_class::CI_PLATFORM_PIPELINES);
            return true;
        }

        if (getenv('PROBO_ENVIRONMENT') !== false) {
            $this->setCIPlatform($ci_platforms_class::CI_PLATFORM_PROBO);
            return true;
        }

        if (getenv('TUGBOAT_URL') !== false) {
            $this->setCIPlatform($ci_platforms_class::CI_PLATFORM_TUGBOAT);
            return true;
        }
        if (getenv('GITLAB_CI') !== false && getenv('GITLAB_CI') == 'true') {
            $this->setCIPlatform($ci_platforms_class::CI_PLATFORM_GITLAB);
            return true;
        }

        return false;
    }

    /**
     * Set CI Platform.
     *
     * @param mixed $platform
     *   CIPlatforms constant.
     *
     * @return bool
     *   false if invalid platform specified.
     */
    public function setCIPlatform($platform)
    {
        $ci_platforms_class = $this->getCIPlatformsClass();
        if (!$ci_platforms_class::isValidValue($platform, true)) {
            return false;
        }
        $this->ciPlatform = $platform;
        return true;
    }

    /**
     * Get CI Platform.
     * @return mixed
     *   CIPlatforms constant
     */
    public function getCIPlatform()
    {
        return $this->ciPlatform;
    }

    /**
     * Determine Hosting Platform based on env variables or other criteria.
     * @return bool
     *   true if a platform was detected.
     */
    protected function determineHostingPlatform()
    {
        $hosting_platforms_class = $this->getHostingPlatformsClass();
        // Check for PROJECT_HOSTING_PLATFORM override first.
        if ($hosting_platform = getenv(self::PROJECT_HOSTING_PLATFORM_ENV_OVERRIDE)) {
            if ($hosting_platforms_class::isValidValue($hosting_platform, true)) {
                $this->setHostingPlatform($hosting_platform);
                return true;
            }
        }

        if (getenv('PANTHEON_ENVIRONMENT') !== false) {
            $this->setHostingPlatform($hosting_platforms_class::HOSTING_PLATFORM_PANTHEON);
            return true;
        }

        if (getenv('AH_SITE_ENVIRONMENT') !== false) {
            $this->setHostingPlatform($hosting_platforms_class::HOSTING_PLATFORM_ACQUIA);
            return true;
        }

        return false;
    }

    /**
     * Set Hosting Platform.
     *
     * @param mixed $platform
     *   HostingPlatforms constant.
     *
     * @return bool
     *   false if invalid platform.
     */
    public function setHostingPlatform($platform)
    {
        $hosting_platforms_class = $this->getHostingPlatformsClass();
        if (!$hosting_platforms_class::isValidValue($platform, true)) {
            return false;
        }
        $this->hostingPlatform = $platform;
        return true;
    }

    /**
     * Get Hosting Platform.
     * @return mixed
     *   HostingsPlatforms constant
     */
    public function getHostingPlatform()
    {
        return $this->hostingPlatform;
    }

    /**
     * Determine Hosting Platform based on env variables or other criteria.
     *
     * @return bool
     *   true if a platform was detected. Sets to Local by default.
     */
    protected function determineEnvironmentType()
    {
        $env_types_class = $this->getEnvironmentTypesClass();
        $hosting_platforms_class = $this->getHostingPlatformsClass();

        // Check for SERVER_ENVIRONMENT override first.
        if ($server_env = getenv(self::PROJECT_ENVIRONMENT_TYPE_ENV_OVERRIDE)) {
            if ($env_types_class::isValidValue($server_env, true)) {
                $this->setEnvironmentType($server_env);
                return true;
            }
        }

        // Detection of a CI platform automatically sets env type to CI.
        if (!empty($this->getCIPlatform())) {
            $this->setEnvironmentType($env_types_class::ENV_TYPE_CI);
            return true;
        }

        switch ($this->getHostingPlatform()) {
            case $hosting_platforms_class::HOSTING_PLATFORM_PANTHEON:
                switch (getenv('PANTHEON_ENVIRONMENT')) {
                    case 'dev':
                        $this->setEnvironmentType($env_types_class::ENV_TYPE_DEV);
                        return true;

                    case 'test':
                        $this->setEnvironmentType($env_types_class::ENV_TYPE_TEST);
                        return true;

                    case 'live':
                        $this->setEnvironmentType($env_types_class::ENV_TYPE_PROD);
                        return true;
                }
                break;
        }

        // Set to Local by default.
        $this->setEnvironmentType($env_types_class::ENV_TYPE_LOCAL);
        return false;
    }

    /**
     * Set Hosting Platform.
     *
     * @param mixed $env_type
     *   EnvironmentTypes constant.
     *
     * @return bool
     *   false if invalid platform.
     */
    public function setEnvironmentType($env_type)
    {
        $env_types_class = $this->getEnvironmentTypesClass();
        if (!$env_types_class::isValidValue($env_type, true)) {
            return false;
        }
        $this->environmentType = $env_type;
        return true;
    }

    /**
     * Get Hosting Platform.
     * @return mixed
     *   HostingsPlatforms constant
     */
    public function getEnvironmentType()
    {
        return $this->environmentType;
    }

    /**
     * Get Loaded Settings Files.
     * @return array
     *   Array of files loaded.
     */
    public function getSettingsFiles()
    {
        return $this->settingsFiles;
    }

    /**
     * Add Loaded Settings File.
     *
     * @param string $path_to_file
     *   Path to file being loaded.
     */
    public function addSettingsFiles($path_to_file)
    {
        $this->settingsFiles[] = $path_to_file;
    }

    /**
     * Scans folder structure for settings file includes.
     */
    protected function scanSettingsFiles()
    {
        $hosting_platform = $this->getHostingPlatform();

        // Include common environment settings files.
        $env_types = ['common'];

        // Include current environment type settings files.
        if ($current_env_type = $this->getEnvironmentType()) {
            $env_types[] = $current_env_type;
        }

        foreach ($env_types as $env_type) {
            // Include per-environment settings files.
            // First include non-platform specific files.
            $settings_file_pattern = $this->settings_root_dir . "/{$env_type}/settings.*.php";
            if ($project_settings_files = glob($settings_file_pattern)) {
                foreach ($project_settings_files as $project_settings_file) {
                    $this->addSettingsFiles($project_settings_file);
                }
            }

            // Second, include platform specific files.
            if (!empty($hosting_platform)) {
                $settings_file_pattern = $this->settings_root_dir .
                    "/{$env_type}/{$hosting_platform}/settings.*.php";
                if ($project_settings_files = glob($settings_file_pattern)) {
                    foreach ($project_settings_files as $project_settings_file) {
                        $this->addSettingsFiles($project_settings_file);
                    }
                }
            }
        }
    }

    /**
     * Loads custom class overrides from environment src folder.
     */
    protected function loadCustomClasses()
    {
        // Add Class Overrides
        $settings_file_pattern = $this->settings_root_dir . "/src/*.php";
        if ($project_settings_files = glob($settings_file_pattern)) {
            foreach ($project_settings_files as $project_settings_file) {
                require_once($project_settings_file);
            }
        }
    }

    /**
     * @return bool
     */
    public function detectedHostingPlatform()
    {
        return $this->detected_hosting_platform;
    }

    /**
     * @return bool
     */
    public function detectedCiPlatform()
    {
        return $this->detected_ci_platform;
    }

    /**
     * @return bool
     */
    public function detectedEnvironmentType()
    {
        return $this->detected_environment_type;
    }

    /**
     * Outputs shell exports for platform/environments.
     *
     * Intended to be run via '. bin/init_project_settings.sh'
     */
    public function printShellExports()
    {
        $exports = 'export ' . self::PROJECT_HOSTING_PLATFORM_ENV_OVERRIDE . "='{$this->getHostingPlatform()}';" .
            'export ' . self::PROJECT_CI_PLATFORM_ENV_OVERRIDE . "='{$this->getCIPlatform()}';" .
            'export ' . self::PROJECT_ENVIRONMENT_TYPE_ENV_OVERRIDE . "='{$this->getEnvironmentType()}';";
        echo $exports;
    }

    /**
     * Sets local environment variables.
     */
    public function setEnvVariables()
    {
        putenv(self::PROJECT_HOSTING_PLATFORM_ENV_OVERRIDE . "={$this->getHostingPlatform()}");
        putenv(self::PROJECT_CI_PLATFORM_ENV_OVERRIDE . "={$this->getCIPlatform()}");
        putenv(self::PROJECT_ENVIRONMENT_TYPE_ENV_OVERRIDE . "={$this->getEnvironmentType()}");
    }
}
