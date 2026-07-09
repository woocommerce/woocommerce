/**
 * External dependencies
 */
const wordpress = require( '@wordpress/eslint-plugin' );

/**
 * Internal dependencies
 */
const reactTestingLibraryConfig = require( './react-testing-library' );

const TEST_FILES = [
	'**/@(test|__tests__)/**/*.js',
	'**/?(*.)test.js',
	'**/tests/**/*.js',
];

/**
 * Restrict a flat config array to the given files.
 *
 * Upstream test configs are shipped unscoped, so consumers must narrow them.
 *
 * @param {Array}          configs Flat config objects.
 * @param {Array<string>}  files   Glob patterns to scope them to.
 * @return {Array} Scoped flat config objects.
 */
const scopeToFiles = ( configs, files ) =>
	configs.map( ( config ) => ( { ...config, files } ) );

module.exports = [
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
	...scopeToFiles( wordpress.configs[ 'test-unit' ], TEST_FILES ),
	...scopeToFiles( reactTestingLibraryConfig, TEST_FILES ),
	{
		// The `jest` plugin is only registered by the test-unit config above.
		files: TEST_FILES,
		rules: {
			// Temporary conversion to warnings until the below are all handled.
			'jest/no-deprecated-functions': 'warn',
			'jest/valid-title': 'warn',
		},
	},
];
