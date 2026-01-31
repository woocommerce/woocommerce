module.exports = {
	extends: [ 'plugin:playwright/recommended' ],
	rules: {
		'playwright/no-wait-for-timeout': 'error',
		'playwright/no-skipped-test': 'off',
		'no-console': 'off',
		'jest/no-test-callback': 'off',
		'jest/no-disabled-tests': 'off',
		'jest/valid-expect': 'off',
		'jest/expect-expect': 'off',
		'jest/no-standalone-expect': 'off',
		'jest/valid-title': 'off',
		'testing-library/await-async-utils': 'off',
	},
	/**
	 * TypeScript files require @typescript-eslint/parser. Without this override,
	 * the parent config's @babel/eslint-parser is used, which produces an AST
	 * incompatible with @typescript-eslint rules (e.g., "node.params is not iterable").
	 * See: https://github.com/typescript-eslint/typescript-eslint/issues/3517
	 */
	overrides: [
		{
			files: [ '**/*.ts', '**/*.tsx' ],
			parser: '@typescript-eslint/parser',
			parserOptions: {
				ecmaVersion: 'latest',
				sourceType: 'module',
			},
		},
	],
};
