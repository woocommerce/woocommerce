module.exports = {
	root: true,
	parser: '@typescript-eslint/parser',
	env: {
		'jest/globals': true,
	},
	ignorePatterns: [
		'dist/',
		'node_modules/',
	],
	"rules": {
		"no-unused-vars": "off",
	},
	extends: [
		'plugin:@wordpress/eslint-plugin/recommended',
		'plugin:jest/recommended',
	],
}
