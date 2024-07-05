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
			'@woocommerce/block-templates',
			'@woocommerce/components',
			'@woocommerce/currency',
			'@woocommerce/data',
			'@woocommerce/currency',
			'@woocommerce/navigation',
			'@woocommerce/settings',
			'@woocommerce/tracks',
			'@wordpress/components',
			'@wordpress/blocks',
			'@wordpress/date',
			'@wordpress/media-utils',
			'@testing-library/react',
		],
		'import/resolver': {
			node: {},
			webpack: {},
			typescript: {},
		},
	},
};
