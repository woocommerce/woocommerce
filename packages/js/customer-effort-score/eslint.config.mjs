/**
 * Internal dependencies
 */
import woocommerce from '@woocommerce/eslint-config';

export default [
	...woocommerce,
	{
		settings: {
			'import/core-modules': [
				'@woocommerce/data',
				'@woocommerce/experimental',
				'@woocommerce/navigation',
				'@woocommerce/tracks',
				'@testing-library/react',
			],
			'import/resolver': {
				node: {},
				webpack: {},
				typescript: {},
			},
		},
	},
];
