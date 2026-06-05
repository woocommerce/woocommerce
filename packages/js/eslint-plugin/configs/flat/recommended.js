/**
 * Flat Config export for `@woocommerce/eslint-plugin`.
 *
 * This export provides a flat-config-compatible array of ESLint config objects
 * for use with ESLint v9+ and the new Flat Config format (eslint.config.js).
 *
 * Example usage in eslint.config.js:
 *
 *   const wc = require( '@woocommerce/eslint-plugin' );
 *   module.exports = [ ...wc.configs[ 'flat/recommended' ] ];
 *
 * Legacy consumers (ESLint v8 and earlier) should continue to use
 * `extends: 'plugin:@woocommerce/eslint-plugin/recommended'` in their .eslintrc.js.
 *
 * @see https://github.com/woocommerce/woocommerce/issues/65458
 * @see https://eslint.org/docs/latest/use/migrate-to-10.0.0
 * @since 2.4.0
 */

'use strict';

const { FlatCompat } = require( '@eslint/eslintrc' );

const compat = new FlatCompat( { baseDirectory: __dirname } );

module.exports = [
	...compat.extends( 'plugin:@woocommerce/eslint-plugin/recommended' ),
];
