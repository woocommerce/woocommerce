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
				// Translation calls use the `__i18n_text_domain__` identifier so
				// each consumer of this package can substitute its own text
				// domain at bundle time (see `development.md`). The default
				// `@wordpress/i18n-text-domain` rule expects a string literal
				// here, so disable it for the package source.
				'@wordpress/i18n-text-domain': 'off',
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
