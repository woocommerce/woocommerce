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
	globalIgnores( [ 'dist/**' ] ),
	{
		rules: {
			'@typescript-eslint/no-explicit-any': 'off',
		},
	},
];
