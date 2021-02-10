module.exports = {
	extends: [
		'plugin:react-hooks/recommended',
		require.resolve( './custom.js' ),
		'plugin:@wordpress/eslint-plugin/recommended',
	],
	parser: '@typescript-eslint/parser',
	globals: {
		wcSettings: 'readonly',
	},
	plugins: [ '@wordpress' ],
	rules: {
		radix: 'error',
		yoda: [ 'error', 'never' ],
	},
};
