# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]
### Added
- compatibility for Magento 2.3.7-p3, 2.4.3-p2 and 2.4.4

## Changed
- updated compatibility list in Readme
- reformatted tables in Readme

## [1.2.1] - 2022-01-10
### Fixed
- using outdated nodejs version in Dockerfiles
- docker-compose.yml version numbers

## [1.2.0] - 2021-11-02
### Added
- utility class \MageGyver\M2devbox\Util\Version
- support for Magento 2.4.2-p2, 2.4.3 and 2.4.3-p1

### Changed
- updated name banner in help message

### Fixed
- using wrong autoload.php when m2devbox was installed as a Composer package

## [1.1.0] - 2021-06-21
### Added
- m2devbox now includes a Redis container and uses it for Magento caching and page caching
- Clear command: added `--yes` option to answer all question with "yes" 

### Changed
- swapped custom fork of `alecrabbit/php-cli-snake` for `aeno/php-slickprogress`
- improved code documentation and readability

### Fixed
- Clear Command: allow clearing multiple versions at once
- Fixed using the wrong PHP Docker build context. This bug prevented having Composer 2 available in supported Magento versions.

## [1.0.0] - 2021-05-12
### Added
- new Command `start-module` to quickly create a blank boilerplate Magento 2 module and start m2devbox with it
- added support for Magento 2.3.7 and 2.4.2-p1
- added CHANGELOG.md

### Changed
- simplified XDebug configuration in Dockerfiles
- changed PHP namespace from "Devbox\" to "MageGyver\M2devbox"

### Fixed
- fixed reading `M2D_APP_DEV` env var from getenv() resulting in `null`
- fixed Magento version string for 2.3.6-p1

### Security
- only extrapolate env vars in recipes that are on the allow-list

## [0.2.2] - 2021-03-25
### Added
- m2devbox can now be required as a Composer dependency
- added self-update command to update m2devbox PHAR installation to the newest version  
- improved composer.json contents

### Changed
- multiple refactorings and moved code to utility classes
- restructured unit tests
- updated Composer dependencies

## [0.2.1] - 2021-03-16
### Added
- support for Magento 2.3.4 through 2.4.2
- improved README (installation, usage and config; new demo gif)
- added funding information

### Changed
- updated Composer dependencies

## [0.2.0] - 2021-03-10
### Added
- initial public release

[Unreleased]: https://github.com/MageGyver/m2devbox/compare/1.2.1...HEAD
[1.2.1]: https://github.com/MageGyver/m2devbox/compare/1.2.0...1.2.1
[1.2.0]: https://github.com/MageGyver/m2devbox/compare/1.1.0...1.2.0
[1.1.0]: https://github.com/MageGyver/m2devbox/compare/1.0.0...1.1.0
[1.0.0]: https://github.com/MageGyver/m2devbox/compare/0.2.2...1.0.0
[0.2.2]: https://github.com/MageGyver/m2devbox/compare/0.2.1...0.2.2
[0.2.1]: https://github.com/MageGyver/m2devbox/compare/0.2...0.2.1
[0.2.0]: https://github.com/MageGyver/m2devbox/releases/tag/0.2
