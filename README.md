[![Build Status](https://travis-ci.com/kducharm/project_settings.svg?branch=master)](https://travis-ci.com/kducharm/project_settings)

# Project Settings

## About

This project is intended to aid in loading of various PHP settings files based on hosting platforms, CI platforms, and environment types. It allows a structure so that individual settings files can be placed in proper folders depending on a project's needs.

**Project File Structure**
* `bin/` - Contains files to be used by shell scripts for environment variables
* `src/` - Project Settings class files
* `environments/` - Settings folder structure for each environment / hosting platform root
* `environments/common` - Settings files for all environments
* `environments/common/PLATFORM` - Settings files for specific platform
* `environments/ENVIRONMENT` - Settings files for specific environment
* `environments/ENVIRONMENT/PLATFORM` - Settings files for specific environment and specific platform

## Usage


* Install via `composer require kducharm/project_settings`.
* Copy the `environments` folder to a location in your project outside of the web root.
* Either set environment variable `PROJECT_SETTINGS_ENV_FOLDER=/path/to/environments/`
* Add the following PHP code to your bootstrap:
``` php
$project_settings = new Kducharm\ProjectSettings\ProjectSettings(); // If env variable not set, pass in '/path/to/environments/' to ProjectSettings().
foreach ($project_settings->getSettingsFiles() as $project_settings_file) {
  require_once $project_settings_file;
}
```

### Environment Variable Overrides
The following environment variables allow for overriding the hosting platform, CI platform, and environment type of the current server.
Their available values are set in `src/ProjectSettingsConstants.php`:
* `PROJECT_HOSTING_PLATFORM` - Hosting platform such as `acquia`, `docker`, `aws`, `pantheon`
* `PROJECT_CI_PLATFORM` - CI platforms such as `gitlab`, `probo`, `travis`, `tugboat`, `pipelines`
* `PROJECT_SERVER_ENVIRONMENT` - Server environment such as `local`, `dev`, `stage`, or `prod`

These environment variables can be set in your CI pipelines, docker configuration files, or other location prior to including the following script:
`. bin/init_project_settings.sh`

Once executed, the hosting/server environment will attempt to be automatically detected (Acquia/Pantheon). 
The CI platform will also attempted to be automatically detected, but may need manual override in cases like containerization where the platform's environment variables may not persist.

### Settings Files
Place your settings files named in the pattern `settings.youridentifier.php` in the appropriate folder.
Examples may include:

`environments/common/settings.behat.php` and `environments/local/settings.behat.php` to change Behat behavior for a local environment.

`environments/common/settings.mail.php` and `environments/prod/settings.mail.php` to change mail settings on production.

`environments/ci/probo/settings.files.php` and `environments/ci/travis/settings.files.php` to set different file locations for TravisCI and Probo.

### Loading Settings Files
Include `settings.project.php` in your project to load all discovered settings files based on the environment/hosting platform.
