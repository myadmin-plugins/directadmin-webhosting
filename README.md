# MyAdmin DirectAdmin Webhosting Plugin

[![Tests](https://github.com/detain/myadmin-directadmin-webhosting/actions/workflows/tests.yml/badge.svg)](https://github.com/detain/myadmin-directadmin-webhosting/actions/workflows/tests.yml)
[![License: LGPL-2.1](https://img.shields.io/badge/License-LGPL%20v2.1-blue.svg)](https://opensource.org/licenses/LGPL-2.1)

MyAdmin plugin for DirectAdmin web hosting management. Provides automated provisioning, suspension, reactivation, termination, and single-sign-on (SSO) login for DirectAdmin-based hosting accounts through the DirectAdmin API.

## Features

- Automated account creation (user and reseller) via DirectAdmin API
- Service suspension, unsuspension, and termination
- One-time URL login key generation for SSO
- IP address change support
- Event-driven architecture using Symfony EventDispatcher
- Built-in HTTPSocket class for DirectAdmin API communication

## Requirements

- PHP >= 5.0
- ext-curl
- Symfony EventDispatcher ^5.0

## Installation

```bash
composer require detain/myadmin-directadmin-webhosting
```

## Usage

The plugin registers event hooks automatically when loaded by the MyAdmin plugin system. It listens for webhosting lifecycle events (activate, deactivate, reactivate, terminate) and handles DirectAdmin API interactions.

## API Reference

- **Plugin** - Main plugin class providing event handlers for the MyAdmin system
- **HTTPSocket** - HTTP client class for communicating with the DirectAdmin API over curl

## Running Tests

```bash
composer install
vendor/bin/phpunit
```

## License

This package is licensed under the [LGPL-2.1](LICENSE).

## Links

- [DirectAdmin API Documentation](https://www.directadmin.com/api.php)
- [InterServer](https://my.interserver.net/)
