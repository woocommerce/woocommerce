module.exports = {
	extends: [ 'plugin:@woocommerce/eslint-plugin/recommended', 'plugin:xstate/all' ],
	plugins: [ 'xstate' ],
	root: true,
	overrides: [
		{
			files: [ 'client/**/*.js', 'client/**/*.jsx', 'client/**/*.tsx' ],
			rules: {
				'react/react-in-jsx-scope': 'off',
				'@typescript-eslint/no-use-before-define': 'warn',
			},
		},
	],
};
