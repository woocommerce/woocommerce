# Unreleased

## Fixed
- Removed the restart policy from e2e containers
- Makes sure that the php containers are only spun up when the db containers is healthy and ready to accept connections
- Wait for WordPress itself to be "healthy and ready" when running `pnpm docker:up`
- Linting errors

## Changed
- Updated `resolveSingleE2EPath` 
  - it resolves the full path if the filePath is valid
  - otherwise, it removes `tests/e2e` from the given filePath before resolving a full path.
- Updated `getLatestReleaseZipUrl` to make use of the assets download url over the archive zip.


## Added

- Added `post-results-to-github-pr.js` to post test results to a GitHub PR.
- Added more entries to `default.json`
- Added test retry support
- Save `test-results.json` from test runs to the `tests/e2e` folder.
- Added `upload.ini` which increases the limits for uploading files (such as for plugins) in the Docker environment
- Test setup, scaffolding, and removal via `wc-e2e install` and `wc-e2e uninstall`
- Added `LATEST_WP_VERSION_MINUS` that allows setting a number to subtract from the current WordPress version for the WordPress Docker image.
- Support for PHP_VERSION, MARIADB_VERSION environment variables for built in container initialization
- `resolveLocalE2ePath()` to resolve path to local E2E file
- `resolvePackagePath()` to resolve path to package file
- `WC_E2E_FOLDER` for mapping plugin root to path within repo
- Added the `resolveSingleE2EPath()` method which builds a path to a specific E2E test
- Added the ability to take screenshots from multiple test failures (when retried) in `utils/take-screenshot.js`.
- `docker:wait` to allow for waiting for env to be built without running tests

## Changed

- Updated `external.md` with instructions to manually set up sites for e2e testing.
- Updated `getLatestReleaseZipUrl()` to allow passing in an authorization token and simplified arguments to just the repository name
- Updated `deleteDownloadedPluginFiles()` to also be able to delete directories.

## Fixed

- Updated the browserViewport in `jest.setup.js` to match the `defaultViewport` dimensions defined in `jest-puppeteer.config.js`
- Use consistent `defaultViewport` in both headless and non-headless context
- Fixed issue with docker compose 2 "key cannot contain a space" error.
- Add missing `config` dependency

# 0.2.3

## Added

- `addConsoleSuppression()`, `removeConsoleSuppression()` utilities to allow suppressing deprecation warnings.
- `updateReadyPageStatus()` utility to update the status of the ready page.
- Added plugin upload functionality util that provides a method to pull a plugin zip from a remote location:
  - `getRemotePluginZip( fileUrl )` to get the remote zip. Returns the filepath of the zip location.
- Added plugin zip utility functions:
  - `checkNestedZip( zipFilePath, savePath )` checks a plugin zip file for any nested zip files. If one is found, it is extracted. Returns the path where the zip file is located.
  - `downloadZip( fileUrl, downloadPath )` downloads a plugin zip file from a remote location to the provided path.
- Added `getLatestReleaseZipUrl( owner, repository, getPrerelease, perPage )` util function to get the latest release zip from a GitHub repository.
- Added `DEFAULT_TIMEOUT_OVERRIDE` that allows passing in a time in milliseconds to override the default Jest and Puppeteer timeouts.

## Fixed

- Fix latest version tag search paging logic
- Update fallback PHP version to 7.4.22
- Update fallback MariaDB version to 10.6.4
- Update fallback WordPress version to 5.8.0.
- Remove unused WP unit test install script.

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
