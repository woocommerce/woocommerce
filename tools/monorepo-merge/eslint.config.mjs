/**
 * Internal dependencies
 */
import woocommerce from '@woocommerce/eslint-config';

export default [
	...woocommerce,
	// A command-line tool reports progress on the terminal.
	{ rules: { 'no-console': 'off' } },
];
