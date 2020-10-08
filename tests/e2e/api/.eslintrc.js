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

		'no-useless-constructor': 'off',
		'@typescript-eslint/no-useless-constructor': 2,
	},
	plugins: [
		'@typescript-eslint'
	],
	extends: [
		'plugin:@wordpress/eslint-plugin/recommended-with-formatting'
	],
	overrides: [
		{
			files: [
				'**/*.js',
				'**/*.ts'
			],
			settings: {
				jsdoc: {
					mode: 'typescript',
				},
			},
		},
		{
			files: [
				'**/*.spec.ts',
				'**/*.test.ts'
			],
			rules: {
				'no-console': 'off',
			}
		}
	]
}
