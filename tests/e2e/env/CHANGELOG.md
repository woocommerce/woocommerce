# Unreleased

## Added

- Insert a 12 hour delay in using new docker image tags

## Fixed

- Remove redundant `puppeteer` dependency

# 0.1.6

## Added

- `useE2EEsLintConfig`, `useE2EBabelConfig` config functions
- support for local Jest and Puppeteer configuration with `useE2EJestConfig`, `useE2EJestPuppeteerConfig` config functions
- support for local node configuration
- support for custom port use in included container
- support for running tests against URLs without a port
- support for PHP, MariaDB & WP versions
- support for a non-plugin folder mapping (theme or project)
- support for `JEST_PUPPETEER_CONFIG` environment variable
- implement `@automattic/puppeteer-utils`
- implement `@wordpress/e2e-test-utils`
- enable test debugging with `test:e2e-debug` script

## Fixed

- support for local test configuration 
- support for local Jest setup
- `docker:ssh` script works in any repo

## Changes

- removed the products and orders delete resets
- eliminated the use of docker-compose.yaml in the root of the project

# 0.1.5

- Initial/beta release
