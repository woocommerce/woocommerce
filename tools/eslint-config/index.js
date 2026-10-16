/**
 * External dependencies
 */
const woocommerce = require( '@woocommerce/eslint-plugin' );

/*
 * Rules the ESLint v8 -> v10 upgrade newly surfaced, downgraded to warnings so
 * the migration is not blocked on fixing them. They are relaxed here rather than
 * in the public plugin so that extension authors still get upstream's severities.
 *
 * Each is either a rule that did not exist in the toolchain being replaced, or an
 * existing rule whose implementation got materially stricter, or an existing rule
 * that now reaches TypeScript. Every consumer's lint script used to be a bare
 * `eslint src`, and ESLint 8 without --ext only looked at `.js`; flat config
 * decides extensions from `files`, so the TypeScript that was never linted now is.
 *
 * Two packages carry downgrades of their own alongside these, because they re-set
 * those severities themselves and the later config object wins: client/blocks
 * (import/named, import/no-unresolved, import/no-duplicates,
 * react-hooks/exhaustive-deps, and the TypeScript no-unused-vars/no-shadow it
 * overrides) and plugins/woocommerce (the playwright rules, whose plugin is only
 * registered for its e2e block).
 *
 * Restoring all of them is tracked in
 * https://github.com/woocommerce/woocommerce/issues/66078.
 */
const RELAXED_RULES = {
	// New in @wordpress/eslint-plugin 25, which re-enables what prettier disables.
	curly: 'warn',

	// New in typescript-eslint v8's recommended set.
	'@typescript-eslint/no-require-imports': 'warn',
	'@typescript-eslint/no-unused-expressions': 'warn',
	// New in @wordpress/eslint-plugin 25's TypeScript config.
	'@typescript-eslint/method-signature-style': 'warn',

	/*
	 * eslint-plugin-react-hooks v7 rewrote rules-of-hooks; it now reports code
	 * the v4 implementation accepted.
	 */
	'react-hooks/rules-of-hooks': 'warn',

	// typescript-eslint v8 split ban-types into these two.
	'@typescript-eslint/no-unsafe-function-type': 'warn',
	'@typescript-eslint/no-empty-object-type': 'warn',
	// v8 reports @ts-ignore that v5 accepted.
	'@typescript-eslint/ban-ts-comment': 'warn',
	'no-redeclare': 'warn',

	// Existing rules that now also run against TypeScript.
	'@typescript-eslint/no-unused-vars': 'warn',
	'@typescript-eslint/no-shadow': 'warn',
	'@typescript-eslint/no-use-before-define': 'warn',
	'@typescript-eslint/no-explicit-any': 'warn',
	'import/no-duplicates': 'warn',
	'jsdoc/no-undefined-types': 'warn',
	'no-undef': 'warn',
	'no-console': 'warn',
	'@wordpress/i18n-translator-comments': 'warn',
	'@wordpress/i18n-no-flanking-whitespace': 'warn',
};

/*
 * The jest and testing-library plugins are only registered for test files, so
 * their rules must be relaxed in a block scoped the same way. Referencing them
 * globally would fail with "Could not find plugin".
 */
const TEST_FILES = [
	'**/@(test|__tests__)/**/*.[jt]s?(x)',
	'**/?(*.)test.[jt]s?(x)',
	'**/tests/**/*.[jt]s?(x)',
];

const RELAXED_TEST_RULES = {
	/*
	 * eslint-plugin-testing-library v5 -> v7 added or tightened these, and the
	 * test globs now match TypeScript, so they reach `.tsx` tests that no
	 * testing-library rule had ever run against.
	 */
	'testing-library/await-async-events': 'warn',
	'testing-library/await-async-queries': 'warn',
	'testing-library/no-await-sync-queries': 'warn',
	'testing-library/no-wait-for-side-effects': 'warn',
	'testing-library/no-dom-import': 'warn',
	'testing-library/no-unnecessary-act': 'warn',
	'testing-library/await-async-utils': 'warn',
	'testing-library/no-manual-cleanup': 'warn',
	'testing-library/no-wait-for-multiple-assertions': 'warn',
	// Now reach the TypeScript tests that were previously unlinted.
	'jest/expect-expect': 'warn',
	'jest/no-conditional-expect': 'warn',
	'jest/no-mocks-import': 'warn',
	'jest/no-done-callback': 'warn',
	'jest/no-alias-methods': 'warn',
	'jest/no-export': 'warn',
	'jest/no-jasmine-globals': 'warn',
	'jest/no-identical-title': 'warn',
	'jest/valid-expect': 'warn',
};

/*
 * The project service parse-errors on any linted file its tsconfig omits, so keep
 * the type-aware block off the files packages exclude from their tsconfigs.
 */
const TYPE_AWARE_IGNORES = [
	'**/test/**',
	'**/tests/**',
	'**/__tests__/**',
	'**/__mocks__/**',
	'**/*.test.[jt]s?(x)',
	'**/stories/**',
	'**/*.stories.[jt]s?(x)',
	'**/typings/**',
	'**/*.d.ts',
];

/*
 * The monorepo's own ESLint Flat Config layer.
 *
 * `@woocommerce/eslint-plugin` is public and consumed by third-party extensions,
 * so it stays portable: no type-aware rules, no assumptions about a covering
 * tsconfig. This package is private and is where monorepo-only strictness goes,
 * so we never impose it on extension authors.
 *
 * Rules that require project/type information (such as
 * `@typescript-eslint/no-floating-promises`, which needs
 * `languageOptions.parserOptions.projectService`) belong here rather than in the
 * public plugin.
 */
module.exports = [
	...woocommerce.configs.recommended,
	{
		rules: RELAXED_RULES,
	},
	{
		files: TEST_FILES,
		rules: RELAXED_TEST_RULES,
	},
	{
		files: [ '**/*.ts', '**/*.tsx' ],
		ignores: TYPE_AWARE_IGNORES,
		languageOptions: {
			parserOptions: {
				projectService: true,
			},
		},
		rules: {
			'@typescript-eslint/no-floating-promises': 'error',
		},
	},
];
