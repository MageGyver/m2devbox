# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]
### Added
- added CHANGELOG.md

### Changed
- simplified XDebug configuration in Dockerfiles
- changed PHP namespace from "Devbox\" to "MageGyver\M2devbox"

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

[Unreleased]: https://github.com/MageGyver/m2devbox/compare/0.2.2...HEAD
[0.2.2]: https://github.com/MageGyver/m2devbox/compare/0.2.1...0.2.2
[0.2.1]: https://github.com/MageGyver/m2devbox/compare/0.2...0.2.1
[0.2.0]: https://github.com/MageGyver/m2devbox/releases/tag/0.2
