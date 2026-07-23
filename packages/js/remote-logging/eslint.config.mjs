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
			'import/core-modules': [ ...coreModules ],
			'import/resolver': {
				node: {},
				typescript: {},
			},
		},
	},
];
