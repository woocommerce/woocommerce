module.exports = {
	plugins: [ '@wordpress', '@woocommerce' ],
	rules: {
		'@woocommerce/dependency-group': 'error',
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
