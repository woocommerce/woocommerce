module.exports = {
	extends: ['plugin:@woocommerce/eslint-plugin/recommended'],
	plugins: ['jest'],
	root: true,
	env: {
		'jest/globals': true,
	},
	globals: {
		page: true,
	},
};
