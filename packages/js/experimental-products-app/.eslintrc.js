module.exports = {
	extends: [ 'plugin:@woocommerce/eslint-plugin/recommended' ],
	root: true,
	overrides: [
		{
			files: [ '**/*.js', '**/*.jsx', '**/*.tsx' ],
			rules: {
				'react/react-in-jsx-scope': 'off',
			},
		},
	],
	settings: {
		'import/core-modules': [
			'@woocommerce/data',
			'@woocommerce/settings',
			'@wordpress/components',
			'@wordpress/compose',
			'@wordpress/core-data',
			'@wordpress/data',
			'@wordpress/dataviews',
			'@wordpress/editor',
			'@wordpress/element',
			'@wordpress/html-entities',
			'@wordpress/i18n',
			'@wordpress/icons',
			'@wordpress/private-apis',
			'@wordpress/router',
			'@wordpress/url',
			'@testing-library/react',
			'clsx',
			'react',
		],
		'import/resolver': {
			node: {},
			webpack: {},
			typescript: {},
		},
	},
};
