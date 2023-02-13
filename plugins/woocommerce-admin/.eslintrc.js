module.exports = {
	extends: [ 'plugin:@woocommerce/eslint-plugin/recommended' ],
	root: true,
	plugins: [ 'prettier' ],
	overrides: [
		{
			files: [ 'client/**/*.js', 'client/**/*.jsx', 'client/**/*.tsx' ],
			rules: {
				'react/react-in-jsx-scope': 'off',
				'prettier/prettier': 'error',
			},
		},
	],
};
