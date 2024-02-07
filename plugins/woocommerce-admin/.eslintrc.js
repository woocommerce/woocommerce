module.exports = {
	extends: [
		'plugin:@woocommerce/eslint-plugin/recommended',
		'plugin:xstate/all',
	],
	plugins: [ 'xstate', 'import' ],
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
		'import/resolver': {
			typescript: {
				project: [ 'plugins/woocommerce-admin/tsconfig.json' ],
			},
		},
	},
};
