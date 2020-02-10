module.exports = {
	extends: [ 'plugin:@wordpress/eslint-plugin/recommended', 'prettier' ],
	env: {
		'jest/globals': true,
	},
	globals: {
		wcSettings: 'readonly',
	},
	plugins: [ 'jest', 'woocommerce' ],
	rules: {
		'@wordpress/dependency-group': 'off',
		'woocommerce/dependency-group': 'error',
		'woocommerce/feature-flag': 'error',
		'valid-jsdoc': 'off',
		radix: 'error',
		yoda: [ 'error', 'never' ],
	},
};
