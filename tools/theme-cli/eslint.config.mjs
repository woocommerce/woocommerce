import woocommerce from '../../eslint.config.mjs';

export default [
	...woocommerce,
	{
		ignores: [ '.wp-env/**', 'vendor/**' ],
	},
	{
		files: [ 'scripts/**/*.mjs' ],
		rules: {
			'no-console': 'off',
		},
	},
	{
		files: [ '*.cjs' ],
		rules: {
			'@typescript-eslint/no-require-imports': 'off',
		},
	},
];
