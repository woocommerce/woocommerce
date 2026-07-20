/**
 * Internal dependencies
 */
import woocommerce from '@woocommerce/eslint-config';

export default [
	...woocommerce,
	{
		settings: {
			'import/core-modules': [
				'@woocommerce/date',
				'@woocommerce/navigation',
				'@woocommerce/tracks',
				'@wordpress/api-fetch',
				'@wordpress/core-data',
				'@wordpress/data',
				'redux',
			],
			'import/resolver': {
				node: {},
				typescript: {},
			},
		},
	},
];
