module.exports = {
	extends: [ 'plugin:@woocommerce/eslint-plugin/recommended' ],
	overrides: [
		{
			files: [
				'src/**/*.js',
				'src/**/*.ts',
				'src/**/*.jsx',
				'src/**/*.tsx',
			],
			rules: {
				'react/react-in-jsx-scope': 'off',
				'@wordpress/no-unsafe-wp-apis': 'off',
				'@wordpress/i18n-text-domain': [
					'error',
					{
						allowedTextDomain: [ 'woocommerce' ],
					},
				],
			},
		},
	],
	settings: {
		'import/core-modules': [
			'@wordpress/blocks',
			'@wordpress/block-editor',
			'@wordpress/components',
			'@wordpress/core-data',
			'@wordpress/date',
			'@wordpress/data',
			'@wordpress/data-controls',
			'@wordpress/editor',
			'@wordpress/element',
			'@wordpress/keycodes',
			'@wordpress/media-utils',
			'@wordpress/notices',
			'@wordpress/hooks',
			'@wordpress/preferences',
		],
		'import/resolver': {
			node: {},
			webpack: {},
			typescript: {},
		},
	},
};
