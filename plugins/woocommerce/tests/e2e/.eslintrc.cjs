const path = require( 'path' );
const rulesDirPlugin = require( 'eslint-plugin-rulesdir' );
rulesDirPlugin.RULES_DIR = `${ __dirname }/rules/blocks`;

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
		 * The e2e tests use dependencies from the parent woocommerce package
		 * (this directory has no package.json of its own). Resolve packageDir from
		 * __dirname so the check is independent of ESLint's working directory: VS
		 * Code / root-level ESLint can run from the monorepo root, and relative
		 * entries would otherwise point at the wrong package.json.
		 *
		 * Both entries must point at directories that contain a package.json:
		 * eslint-plugin-import throws on the first missing one and aborts the
		 * whole dependency merge, so we list the parent woocommerce package and
		 * the monorepo root rather than this dir.
		 */
		'import/no-extraneous-dependencies': [
			'warn',
			{
				packageDir: [
					path.resolve( __dirname, '../..' ),
					path.resolve( __dirname, '../../../..' ),
				],
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
			rules: {
				'@typescript-eslint/no-explicit-any': 'off',
			},
		},
		/*
		 * Blocks e2e subtree (migrated into the core e2e suite during the
		 * QAO-185 merge). These files use the blocks alias universe, so they get
		 * the type-aware parser pointed at tsconfig.blocks.json and the blocks
		 * lint rules that previously applied to the blocks e2e tree.
		 */
		{
			files: [ 'tests/blocks/**', 'utils/blocks/**' ],
			parser: '@typescript-eslint/parser',
			parserOptions: {
				tsconfigRootDir: __dirname,
				project: './tsconfig.blocks.json',
			},
			plugins: [ 'rulesdir' ],
			rules: {
				'rulesdir/no-raw-playwright-test-import': 'error',
				// Since we're restoring the database for each test, hooks other
				// than `beforeEach` don't make sense.
				// See https://github.com/woocommerce/woocommerce/pull/46432.
				'playwright/no-hooks': [ 'error', { allow: [ 'beforeEach' ] } ],
				'no-restricted-syntax': [
					'error',
					{
						selector: 'CallExpression[callee.property.name="$"]',
						message:
							'`$` is discouraged, please use `locator` instead',
					},
					{
						selector: 'CallExpression[callee.property.name="$$"]',
						message:
							'`$$` is discouraged, please use `locator` instead',
					},
					{
						selector:
							'CallExpression[callee.object.name="page"][callee.property.name="waitForTimeout"]',
						message: 'Prefer page.locator instead.',
					},
				],
			},
		},
	],
};
