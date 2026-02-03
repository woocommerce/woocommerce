# Technology Stack

**Analysis Date:** 2026-02-02

## Languages

**Primary:**

-   PHP 7.4+ - Backend plugin code in `plugins/woocommerce/`
-   TypeScript 5.7.x - Frontend code in `client/` and `packages/js/`
-   JavaScript (ES6+) - Various frontend modules and build tools

**Secondary:**

-   SCSS/Sass - Styling across frontend applications
-   Shell/Bash - Build and automation scripts

## Runtime

**Environment:**

-   Node.js v20.11.1
-   PHP 7.4 (platform constraint in composer)

**Package Manager:**

-   pnpm 9.15.0 (monorepo)
-   Composer (PHP dependencies)
-   Lockfile: present (pnpm-lock.yaml, composer.lock)

## Frameworks

**Core:**

-   WordPress Plugin Architecture - Main e-commerce plugin
-   React 18.3.x - Admin UI and block editor components
-   WordPress Block Editor - Gutenberg blocks integration
-   Jetpack - Connection and utilities from Automattic

**Testing:**

-   Jest 29.5.x - JavaScript unit testing
-   PHPUnit 9.6 - PHP unit testing
-   Playwright - E2E testing framework
-   Mockery 1.6.6 - PHP mocking

**Build/Dev:**

-   Webpack 5.97.x - Module bundler
-   Babel 7.25.7 - JavaScript transpiler
-   TypeScript 5.7.x - Type checking and compilation
-   ESLint with @wordpress/eslint-plugin - JavaScript linting
-   PHPStan 2.1 - PHP static analysis
-   Husky 9.0.11 - Git hooks
-   Prettier (wp-prettier) - Code formatting

## Key Dependencies

**Critical:**

-   @wordpress/\* packages (wp-6.6) - WordPress core functionality
-   @woocommerce/\* workspace packages - Internal monorepo packages
-   automattic/jetpack-\* packages - Jetpack functionality
-   woocommerce/action-scheduler 3.9.3 - Background job processing

**Infrastructure:**

-   lodash 4.17.21 - Utility functions
-   core-js 3.34.0 - Polyfills
-   regenerator-runtime 0.13.11 - Async/await support

## Configuration

**Environment:**

-   `.nvmrc` - Node version specification (v20)
-   `composer.json` - PHP dependencies and autoloading
-   `package.json` - Node dependencies and scripts
-   Environment variables for database and WordPress configuration

**Build:**

-   `webpack.config.js` files in various client directories
-   `tsconfig.json` files for TypeScript configuration
-   `.eslintrc.js` - Linting configuration
-   `jest.config.js` - Test configuration

## Platform Requirements

**Development:**

-   Node.js v20.11.1
-   PHP 7.4+
-   Docker (for wp-env testing environment)
-   pnpm package manager
-   Composer

**Production:**

-   WordPress 5.6+
-   PHP 7.4+
-   MySQL 5.6+ or MariaDB equivalent
-   Web server (Apache/Nginx)

---

_Stack analysis: 2026-02-02_
