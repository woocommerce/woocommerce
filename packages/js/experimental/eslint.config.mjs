/**
 * Internal dependencies
 */
import woocommerce from '@woocommerce/eslint-config';

export default [
	...woocommerce,
	{
		settings: {
			'import/core-modules': [
				'@woocommerce/components',
				'@wordpress/components',
				'@storybook/react',
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
