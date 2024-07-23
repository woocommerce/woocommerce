module.exports = {
	extends: [ 'plugin:@woocommerce/eslint-plugin/recommended' ],
	root: true,
	ignorePatterns: [ '**/test/*.ts', '**/test/*.tsx' ],
	overrides: [
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
	],
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
};
