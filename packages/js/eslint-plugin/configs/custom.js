module.exports = {
	plugins: [ '@wordpress' ],
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
	overrides: [
		{
			files: [
				'**/@(test|__tests__)/**/*.js',
				'**/?(*.)test.js',
				'**/tests/**/*.js',
			],
			extends: [
				'plugin:@wordpress/eslint-plugin/test-unit',
				require.resolve( './react-testing-library' ),
			],
		},
	],
};
