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
