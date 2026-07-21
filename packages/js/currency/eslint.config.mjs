/**
 * Internal dependencies
 */
import woocommerce from '@woocommerce/eslint-config';

export default [
	...woocommerce,
	{
		settings: {
			'import/core-modules': [
				'@woocommerce/number',
				'@woocommerce/settings',
			],
			'import/resolver': {
				node: {},
				typescript: {},
			},
		},
	},
];
