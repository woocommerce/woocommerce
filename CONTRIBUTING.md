# Contributing to WooCommerce

This is a quick reference for common commands used during development. For broader contribution guidelines, see the [Contributing to WooCommerce](https://developer.woocommerce.com/docs/contribution/contributing/) docs. For detailed development notes, see [DEVELOPMENT.md](DEVELOPMENT.md).

## Tooling

- [NVM](https://github.com/nvm-sh/nvm#installing-and-updating) (recommended for Node version management)
- [Node.js](https://nodejs.org/)
- [PNPM](https://pnpm.io/installation)
- [PHP](https://www.php.net/manual/en/install.php)
- [Composer](https://getcomposer.org/doc/00-intro.md)
- [Docker](https://docs.docker.com/get-docker/) (for running tests and local environments)

A POSIX-compliant OS (Linux, macOS) is assumed. On Windows, use [WSL](https://learn.microsoft.com/en-us/windows/wsl/install).

## Initial install

```sh
# Use the pinned Node version from .nvmrc
nvm install
# Install JS and PHP dependencies
pnpm install
```

## Monorepo filtering

This is a PNPM workspaces monorepo. Use `--filter` to target individual projects:

```sh
# Build WooCommerce Core + deps
pnpm --filter='@woocommerce/plugin-woocommerce' build
# Lint a specific package
pnpm --filter='@woocommerce/components' lint
# Build all JS packages
pnpm --filter='./packages/js/*' build
# Build only what changed
pnpm --filter='[HEAD^1]' build
```

See [DEVELOPMENT.md](DEVELOPMENT.md) for more filtering examples and [tools/README.md](tools/README.md) for monorepo infrastructure details.

## Local WordPress environment

The repository uses [`@wordpress/env`](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/) (`wp-env`) for local development environments.

```sh
cd plugins/woocommerce
# Start the environment (creates it if needed, pulls latest config)
pnpm env:dev
# Stop the environment
pnpm env:stop
# Remove all environment files
pnpm env:destroy
```

## Build

```sh
# Build everything
pnpm build
# Build WooCommerce Core
pnpm --filter='@woocommerce/plugin-woocommerce' build
# Create woocommerce.zip
pnpm --filter='@woocommerce/plugin-woocommerce' build:zip
```

For active development with file watching:

```sh
# Watch and rebuild on changes
pnpm --filter='@woocommerce/plugin-woocommerce' watch:build
```

## Tests

```sh
# PHP unit tests (requires wp-env)
cd plugins/woocommerce
# Start the test environment
pnpm env:dev
# Run all PHP unit tests
pnpm test:unit:env
# Run a specific test class
pnpm test:unit:env -- --filter=TestClassName
# Watch mode
pnpm test:unit:env:watch

# E2E tests (requires Docker)
cd plugins/woocommerce
# Start the E2E environment
pnpm env:start
# Run Playwright E2E tests
pnpm test:e2e

# JavaScript tests
pnpm --filter='@woocommerce/admin-library' test:js
pnpm --filter='@woocommerce/block-library' test:js
```

See the [unit tests README](plugins/woocommerce/tests/README.md), [E2E tests README](plugins/woocommerce/tests/e2e-pw/README.md), and [performance tests README](plugins/woocommerce/tests/performance/README.md) for full details.

## Linting and static analysis

```sh
# Lint everything
pnpm lint
# Lint changed PHP files
pnpm --filter='@woocommerce/plugin-woocommerce' lint:php:changes
# Lint PHP changes on branch (vs trunk)
pnpm --filter='@woocommerce/plugin-woocommerce' lint:php:changes:branch
# Auto-fix PHP lint issues
pnpm --filter='@woocommerce/plugin-woocommerce' lint:php:fix -- path/to/file.php
# Lint JS/TS (ESLint)
pnpm --filter='@woocommerce/plugin-woocommerce' lint:lang:js
```

PHPStan:

```sh
cd plugins/woocommerce
composer exec -- phpstan analyse path/to/File.php --memory-limit=2G
```

See the [Coding Standards](https://developer.woocommerce.com/docs/best-practices/coding-standards/) docs and the [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/) for conventions.

## Changelog

Every PR that changes code in a project requires a changelog entry:

```sh
# Interactive prompt for WooCommerce Core
pnpm --filter='@woocommerce/plugin-woocommerce' changelog add
```

Replace `@woocommerce/plugin-woocommerce` with the relevant package name if your changes affect a different project.

For the full PR workflow, changelog conventions, and coding guidelines, see [Contributing to WooCommerce](.github/CONTRIBUTING.md) and the [contribution docs](https://developer.woocommerce.com/docs/contribution/contributing/).

## Repository structure

```
plugins/                   # WordPress plugins
  woocommerce/             # WooCommerce Core plugin
    src/                   #   Modern PHP (PSR-4, DI container)
    includes/              #   Legacy PHP
    client/admin/          #   Admin React/TypeScript app
    tests/                 #   PHP unit, E2E, performance tests
packages/                  # Shared libraries
  js/                      #   JavaScript/TypeScript packages
  php/                     #   PHP packages
tools/                     # Monorepo utilities and scripts
```

See [plugins/woocommerce/src/README.md](plugins/woocommerce/src/README.md) for modern PHP architecture and [tools/README.md](tools/README.md) for monorepo tooling.
