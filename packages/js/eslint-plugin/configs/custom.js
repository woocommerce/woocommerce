/**
 * External dependencies
 */
const { defineConfig } = require( 'eslint/config' );
const wordpress = require( '@wordpress/eslint-plugin' );

/**
 * Internal dependencies
 */
const reactTestingLibraryConfig = require( './react-testing-library' );

/*
 * The eslintrc configs these globs came from only matched `.js`, because a bare
 * `eslint src` under ESLint 8 never looked at anything else. Flat config lints
 * TypeScript too, so match it here or `jest` goes unregistered in `.ts` tests and
 * their eslint-disable directives resolve to unknown rules.
 */
const TEST_FILES = [
	'**/@(test|__tests__)/**/*.[jt]s?(x)',
	'**/?(*.)test.[jt]s?(x)',
	'**/tests/**/*.[jt]s?(x)',
];

module.exports = defineConfig( [
	{
		rules: {
			// Group external imports before internal ones (`~/…` and relative).
			'import/order': [
				'error',
				{
					groups: [
						[ 'builtin', 'external', 'internal' ],
						[ 'parent', 'sibling', 'index' ],
					],
					pathGroups: [ { pattern: '~/**', group: 'parent' } ],
					pathGroupsExcludedImportTypes: [ 'builtin' ],
				},
			],
		},
		settings: {
			jsdoc: {
				mode: 'typescript',
			},
		},
	},
	{
		// Upstream ships these unscoped; `extends` narrows them to test files.
		files: TEST_FILES,
		extends: [
			wordpress.configs[ 'test-unit' ],
			reactTestingLibraryConfig,
		],
		rules: {
			// Temporary conversion to warnings until the below are all handled.
			'jest/no-deprecated-functions': 'warn',
			'jest/valid-title': 'warn',
		},
	},
] );
