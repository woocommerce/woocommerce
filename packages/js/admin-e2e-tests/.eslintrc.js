module.exports = {
	extends: [
		'plugin:@typescript-eslint/recommended',
		'plugin:@woocommerce/eslint-plugin/recommended',
	],
	root: true,
	plugins: [ 'prettier' ],
	rules: {
		'prettier/prettier': 'error',
	},
};
