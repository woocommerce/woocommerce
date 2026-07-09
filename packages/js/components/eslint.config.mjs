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
			'import/core-modules': [
				'@woocommerce/components',
				'@woocommerce/currency',
				'@woocommerce/data',
				'@woocommerce/date',
				'@woocommerce/navigation',
				'@storybook/react',
				'@automattic/tour-kit',
				'@wordpress/blocks',
				'@wordpress/components',
				'@wordpress/element',
				'@wordpress/media-utils',
				'dompurify',
				'downshift',
				'moment',
			],
			'import/resolver': {
				node: {},
				webpack: {},
				typescript: {},
			},
		},
	},
	{
		files: [
			'**/stories/*.js',
			'**/stories/*.jsx',
			'**/docs/example.js',
		],
		rules: {
			'import/no-unresolved': [
				'warn',
				{ ignore: [ '@woocommerce/components' ] },
			],
		},
	},
];
