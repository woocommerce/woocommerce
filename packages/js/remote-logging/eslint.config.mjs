/**
 * Internal dependencies
 */
import woocommerce from '@woocommerce/eslint-config';

export default [
	...woocommerce,
	{
		ignores: [ '**/test/*.ts', '**/test/*.tsx' ],
	},
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
