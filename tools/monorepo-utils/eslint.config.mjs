/**
 * Internal dependencies
 */
import woocommerce from '@woocommerce/eslint-config';

export default [
	...woocommerce,
	{
		ignores: [ 'dist/**' ],
	},
	{
		rules: {
			'@typescript-eslint/no-explicit-any': 'off',
		},
	},
];
