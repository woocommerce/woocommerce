module.exports = {
	parser: '@typescript-eslint/parser',
	env: {
		'jest/globals': true
	},
	ignorePatterns: [
		'dist/',
		'node_modules/'
	],
	rules: {
		'no-unused-vars': 'off',
		'no-dupe-class-members': 'off',
	},
	extends: [
		'plugin:@wordpress/eslint-plugin/recommended-with-formatting'
	],
	overrides: [
		{
			'files': [ '**/*.ts' ]
		},
		{
			'files': [ '**/*.spec.ts' ],
			'rules': {
				'no-console': 'off',
			}
		}
	]
}
