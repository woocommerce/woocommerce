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
			'@woocommerce/data',
			'@woocommerce/navigation',
			'@woocommerce/tracks',
		],
		'import/resolver': {
			node: {},
			webpack: {},
			typescript: {},
		},
	},
};
