/**
 * Internal dependencies
 */
import woocommerce from '@woocommerce/eslint-config';

export default [
	...woocommerce,
	{
		settings: {
			'import/core-modules': [
				'@woocommerce/experimental',
				'@woocommerce/components',
				'@woocommerce/tracks',
			],
			'import/resolver': {
				node: {},
				webpack: {},
				typescript: {},
			},
		},
	},
];
