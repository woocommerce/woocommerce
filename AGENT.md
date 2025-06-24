# WooCommerce Development Guide

## Build/Test Commands
- **Install & Build**: `nvm install && pnpm install --frozen-lockfile && pnpm build`
- **Watch**: `pnpm --filter=@woocommerce/plugin-woocommerce watch:build`
- **Test (JS)**: `pnpm test` (all), `pnpm --filter='@woocommerce/components' test` (single package)  
- **Test (PHP)**: `cd plugins/woocommerce && pnpm run test:php:env {relative_path} --verbose`
- **Lint**: `pnpm lint` (all), `pnpm --filter='@woocommerce/components' lint` (single package)
- **Build specific**: `pnpm --filter='@woocommerce/plugin-woocommerce' build`

## Architecture
- **Monorepo structure**: packages/js/*, packages/php/*, plugins/*, tools/*
- **Main plugin**: plugins/woocommerce/ (core WooCommerce plugin)
- **Client apps**: plugins/woocommerce/client/{admin,blocks,legacy}
- **WordPress integration**: Uses @wordpress/* packages, block editor APIs
- **TypeScript**: Strict mode enabled, uses project references for performance

## Code Style
- **ESLint**: `@woocommerce/eslint-plugin/recommended`
- **Prettier**: `@wordpress/prettier-config`
- **Imports**: External deps first (/** External dependencies */), then internal (/** Internal dependencies */)
- **Types**: Prefer explicit types, use `type` for type imports
- **Naming**: kebab-case for files, PascalCase for components, camelCase for functions
- **WordPress**: Use `__()` for i18n, follow WordPress coding standards for PHP
