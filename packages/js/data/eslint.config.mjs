/**
 * Internal dependencies
 */
import woocommerce from '@woocommerce/eslint-config';
import { coreModules } from '@woocommerce/eslint-config/core-modules.js';

export default [
	...woocommerce,
	{
		settings: {
			'import/core-modules': [
				...coreModules,
				'@wordpress/api-fetch',
			],
			'import/resolver': {
				node: {},
				typescript: {},
			},
		},
	},
];
