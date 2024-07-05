module.exports = {
	extends: [ 'plugin:@woocommerce/eslint-plugin/recommended' ],
	plugins: [ 'import' ],
	root: true,
	overrides: [
		{
			files: [ 'client/**/*.js', 'client/**/*.jsx', 'client/**/*.tsx' ],
			rules: {
				'react/react-in-jsx-scope': 'off',
			},
		},
	],
	settings: {
		'import/core-modules': [
			'@woocommerce/ai',
			'@woocommerce/admin-layout',
			'@woocommerce/components',
			'@woocommerce/customer-effort-score',
			'@woocommerce/currency',
			'@woocommerce/data',
			'@woocommerce/date',
			'@woocommerce/navigation',
			'@woocommerce/number',
			'@woocommerce/onboarding',
			'@woocommerce/tracks',
			'@woocommerce/experimental',
			'@wordpress/block-editor',
			'xstate',
		],
		'import/resolver': {
			node: {},
			webpack: {},
			typescript: {
				project: [ 'plugins/woocommerce-admin/tsconfig.json' ],
			},
		},
	},
};
