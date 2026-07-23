/**
 * External dependencies
 */
import { globalIgnores } from 'eslint/config';

/**
 * Internal dependencies
 */
import woocommerce from '@woocommerce/eslint-config';
import { coreModules } from '@woocommerce/eslint-config/core-modules.js';

export default [
	...woocommerce,
	globalIgnores( [ '**/test/*.ts', '**/test/*.tsx' ] ),
	{
		settings: {
			'import/core-modules': [
				...coreModules,
				'@storybook/react',
				'@automattic/tour-kit',
				'dompurify',
				'downshift',
				'moment',
			],
			'import/resolver': {
				node: {},
				webpack: {},
				typescript: {},
			},
		},
	},
	{
		files: [ '**/stories/*.js', '**/stories/*.jsx', '**/docs/example.js' ],
		rules: {
			'import/no-unresolved': [
				'warn',
				{ ignore: [ '@woocommerce/components' ] },
			],
		},
	},
];
