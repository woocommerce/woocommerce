module.exports = {
	extends: [
		'plugin:@wordpress/eslint-plugin/recommended',
		'prettier',
		'plugin:testing-library/recommended',
		'plugin:testing-library/react',
	],
	env: {
		'jest/globals': true,
	},
	globals: {
		wcSettings: true,
	},
	plugins: [ 'jest', 'testing-library' ],
	rules: {
		'@wordpress/dependency-group': 'off',
		'valid-jsdoc': 'off',
		radix: 'error',
		yoda: [ 'error', 'never' ],
	},
};
