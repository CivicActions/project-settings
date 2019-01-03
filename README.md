[![Build Status](https://travis-ci.com/kducharm/project_settings.svg?branch=master)](https://travis-ci.com/kducharm/project_settings)

# Project Settings

## About

This project is intended to aid in loading of various PHP settings files based on hosting platforms, CI platforms, and environment types. The environment variables for the hosting/CI platform and environment will accessible via shell and in PHP (using `getenv()`). It allows a folder structure so that individual settings files can be placed in proper folders depending on a project's needs.

**Project File Structure**
* `bin/` - Contains files to be used by shell scripts for environment variables
* `src/` - Project Settings class files
* `environments/` - Settings folder structure for each environment / hosting platform root
* `environments/common` - Settings files for all environments
* `environments/common/PLATFORM` - Settings files for specific platform
* `environments/ENVIRONMENT` - Settings files for specific environment
* `environments/ENVIRONMENT/PLATFORM` - Settings files for specific environment and specific platform
* `environments/secrets` - Classes to include for Secrets Management, such as `SampleSecretsManager.php`

## Usage

* Install via `composer require kducharm/project_settings`.
* Copy the `environments` folder to a location in your project outside of the web root.
* Either set environment variable `PROJECT_SETTINGS_ENV_FOLDER=/path/to/environments/` or pass path into ProjectSettings().
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
`eval $($APP_ROOT/vendor/kducharm/project_settings/bin/init_project_settings)`

Once executed, the hosting/server environment will attempt to be automatically detected (Acquia/Pantheon). 
The CI platform will also attempted to be automatically detected, but may need manual override in cases like containerization where the platform's environment variables may not persist.

### Settings Files
Place your settings files named in the pattern `settings.youridentifier.php` in the appropriate folder.
Examples may include:

`environments/common/settings.behat.php` and `environments/local/settings.behat.php` to change Behat behavior for a local environment.

`environments/common/settings.mail.php` and `environments/prod/settings.mail.php` to change mail settings on production.

`environments/ci/probo/settings.files.php` and `environments/ci/travis/settings.files.php` to set different file locations for TravisCI and Probo.

### Secrets Management
An abstract class is provided to aid in retrieval of secrets `src/SecretsManager.php`. A sample implementation is at `environments/secrets/SampleSecretsManager.php`.

To utilize the secrets management functionality, the class should be extended to define the `$project_prefix` string and `$secrets_definitions` array with a project name and a list of secrets that are available.

The default implementation of `getSecret` checks the PHP environment variables available using `getenv()` which would require setting php `variables_order` to include `E` in `php.ini`.
You may extend the `getSecret` function to override its behavior on how to retrieve a secret.

To retrieve a secret in a PHP file:
```
$project_settings = new Kducharm\ProjectSettings\ProjectSettings(); // If env variable not set, pass in '/path/to/environments/' to ProjectSettings().
$secrets_manager = new Kducharm\ProjectSettings\SampleSecretsManager($project_settings);
$secret = $secrets_manager->getSecret('SECRET_NAME');
```

`getSecret()` will perform a lookup of the variable name passed in again an environment-specific variable, then non-specific variable if not found. For example when on dev environment with class `$project_prefix` set to `PROJECT_NAME`:
```
$secret = $secrets_manager->getSecret('SECRET_NAME');
```
will look for the environment variables in this order:
```
PROJECT_NAME_DEV_SECRET_NAME
PROJECT_NAME_SECRET_NAME
```
