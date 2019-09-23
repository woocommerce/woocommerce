module.exports = {
	extends: [ 'plugin:@wordpress/eslint-plugin/recommended', 'prettier' ],
	env: {
		'jest/globals': true,
	},
	globals: {
		wcSettings: true,
	},
	plugins: [ 'jest' ],
	rules: {
		'@wordpress/dependency-group': 'off',
		'valid-jsdoc': 'off',
	},
};
