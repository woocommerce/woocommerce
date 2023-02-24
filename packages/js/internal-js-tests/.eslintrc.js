module.exports = {
	extends: [ 'plugin:@woocommerce/eslint-plugin/recommended' ],
	root: true,
	plugins: [ 'prettier' ],
	rules: {
		'prettier/prettier': 'error',
	},
};
