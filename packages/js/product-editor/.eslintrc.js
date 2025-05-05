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
			'@woocommerce/admin-layout',
			'@woocommerce/block-templates',
			'@woocommerce/components',
			'@woocommerce/customer-effort-score',
			'@woocommerce/currency',
			'@woocommerce/data',
			'@woocommerce/experimental',
			'@woocommerce/expression-evaluation',
			'@woocommerce/navigation',
			'@woocommerce/number',
			'@woocommerce/settings',
			'@woocommerce/tracks',
			'@wordpress/blocks',
			'@wordpress/block-editor',
			'@wordpress/components',
			'@wordpress/core-data',
			'@wordpress/date',
			'@wordpress/element',
			'@wordpress/keycodes',
			'@wordpress/media-utils',
			'@testing-library/react',
			'dompurify',
			'react-router-dom',
		],
		'import/resolver': {
			node: {},
			webpack: {},
			typescript: {},
		},
	},
};
