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
				'@storybook/react-vite',
				'react-transition-group/CSSTransition',
				'dompurify',
			],
			'import/resolver': {
				node: {},
				webpack: {},
				typescript: {},
			},
		},
	},
];
