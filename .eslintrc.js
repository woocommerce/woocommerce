module.exports = {
	root: true,
	extends: [ 'plugin:@woocommerce/eslint-plugin/recommended' ],
	plugins: [ 'prettier' ],
	rules: {
		'prettier/prettier': 'error',
	},
};
