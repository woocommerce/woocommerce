/**
 * Internal dependencies
 */
import woocommerce from '@woocommerce/eslint-config';

export default [
	...woocommerce,
	{
		files: [ 'src/**/*.js', 'test/**/*.js' ],
		// Extension webpack configs and Node tests use CommonJS.
		rules: {
			'@typescript-eslint/no-require-imports': 'off',
		},
	},
];
