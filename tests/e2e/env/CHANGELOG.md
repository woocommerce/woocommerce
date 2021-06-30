# Unreleased

# 0.2.2

## Added

- `takeScreenshotFor` utility function to take screenshots within tests
- `sendFailedTestScreenshotToSlack` to send the screenshot to the configured Slack channel
- `sendFailedTestMessageToSlack` to send the context for screenshot to the configured Slack channel
- `toBeInRange` expect numeric range matcher

# 0.2.1

## Added

- Support for screenshots on test errors
- Slackbot to report errors to Slack channel

## Fixed

- Update `wc-e2e` script to fix an issue with directories with a space in their name

# 0.2.0

## Added

- support for custom container name
- Insert a 12 hour delay in using new docker image tags
- Package `bin` script `wc-e2e`
- WP Mail Log plugin as part of container initialization

## Fixed

- Return jest exit code from `npx wc-e2e test:e2e*`
- Remove redundant `puppeteer` dependency
- Support for admin user configuration from `default.json`

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
