module.exports = {
	extends: [ 'plugin:@wordpress/eslint-plugin/recommended', 'prettier' ],
	env: {
		'jest/globals': true,
	},
	globals: {
		wc_product_block_data: true,
		wcSettings: true,
	},
	plugins: [ 'jest' ],
	rules: {
		'@wordpress/dependency-group': 'off',
		camelcase: [
			'error',
			{
				allow: [ 'wc_product_block_data' ],
				properties: 'never',
			},
		],
		'valid-jsdoc': 'off',
	},
};
