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
		// Pre-existing warnings - to be addressed in future PRs.
		'@typescript-eslint/no-non-null-assertion': 'off',
		'playwright/no-conditional-in-test': 'off',
		'playwright/no-nested-step': 'off',
		'playwright/no-wait-for-selector': 'off',
		'playwright/expect-expect': 'off',
		'playwright/no-eval': 'off',
		/*
		 * The e2e-pw tests use dependencies from the parent woocommerce package.
		 * This configuration tells ESLint to check both the local package.json
		 * and the parent package.json when validating imports.
		 */
		'import/no-extraneous-dependencies': [
			'warn',
			{
				packageDir: [ '.', '../..' ],
			},
		],
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
