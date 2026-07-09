/**
 * External dependencies
 */
const woocommerce = require( '@woocommerce/eslint-plugin' );

/*
 * The monorepo's own ESLint Flat Config layer.
 *
 * `@woocommerce/eslint-plugin` is public and consumed by third-party extensions,
 * so it stays portable: no type-aware rules, no assumptions about a covering
 * tsconfig. This package is private and is where monorepo-only strictness goes,
 * so we never impose it on extension authors.
 *
 * It is a pass-through today. Rules that require project/type information (such
 * as `@typescript-eslint/no-floating-promises`, which needs
 * `languageOptions.parserOptions.projectService`) belong here rather than in the
 * public plugin.
 */
module.exports = [ ...woocommerce.configs.recommended ];
