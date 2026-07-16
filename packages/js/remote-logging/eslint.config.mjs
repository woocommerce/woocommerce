/**
 * External dependencies
 */
import { globalIgnores } from 'eslint/config';

/**
 * Internal dependencies
 */
import woocommerce from '@woocommerce/eslint-config';

export default [
	...woocommerce,
	globalIgnores( [ '**/test/*.ts', '**/test/*.tsx' ] ),
	{
		settings: {
			'import/core-modules': [ '@woocommerce/settings' ],
			'import/resolver': {
				node: {},
				typescript: {},
			},
		},
	},
];
