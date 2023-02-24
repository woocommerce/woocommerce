module.exports = {
	extends: [ 'plugin:@woocommerce/eslint-plugin/recommended' ],
	plugins: [ 'jest', 'prettier' ],
	root: true,
	rules: {
		// These warning rules are stop gaps for eslint issues that need to be fixed later.
		'@typescript-eslint/no-explicit-any': 'off',
		'@typescript-eslint/no-use-before-define': 'off',
		'@typescript-eslint/ban-ts-comment': 'off',
		'@typescript-eslint/no-unused-vars': 'off',
		'prettier/prettier': 'error',
	},
};
