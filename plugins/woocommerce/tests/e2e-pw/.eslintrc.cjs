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
	overrides: [
		/*
		 * The default ESLint parser will not handle TypeScript syntax.
		 * This override switches to the TypeScript parser for .ts and .tsx
		 * files so that ESLint can lint them without reporting parse errors.
		 */
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
