module.exports = {
	extends: [
		'plugin:@wordpress/eslint-plugin/recommended'
	],
	env: {
		'jest/globals': true,
	},
	globals: {
		wc_product_block_data: true,
		wcSettings: true,
	},
	plugins: [
		'jest',
	],
	rules: {
		'@wordpress/dependency-group': 'off',
		'valid-jsdoc': 'off',
	}
};
