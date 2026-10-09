/**
 * External dependencies
 */
import { globalIgnores } from 'eslint/config';
import globals from 'globals';

/**
 * Internal dependencies
 */
import woocommerce from '@woocommerce/eslint-config';

export default [
	// node_modules is ignored by default.
	globalIgnores( [
		'**/*.min.js',
		'build/**',
		'build-module/**',
		'vendor/**',
	] ),
	...woocommerce,
	{
		languageOptions: {
			globals: {
				...globals.browser,
				...globals.node,
			},
		},
		rules: {
			camelcase: 'off',
			'react/react-in-jsx-scope': 'off',
			'no-alert': 'off',
		},
	},
];
