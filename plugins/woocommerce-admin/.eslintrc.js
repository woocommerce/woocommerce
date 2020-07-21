module.exports = {
	extends: [
		'plugin:@wordpress/eslint-plugin/recommended',
		'prettier',
		'prettier/react',
		'plugin:testing-library/recommended',
		'plugin:testing-library/react',
		'plugin:jest-dom/recommended',
	],
	env: {
		'jest/globals': true,
	},
	globals: {
		wcSettings: true,
	},
	plugins: [ 'jest', 'jest-dom', 'testing-library' ],
	rules: {
		'@wordpress/dependency-group': 'off',
		'valid-jsdoc': 'off',
		radix: 'error',
		yoda: [ 'error', 'never' ],
	},
};
