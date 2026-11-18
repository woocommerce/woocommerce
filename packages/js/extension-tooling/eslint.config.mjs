/**
 * Internal dependencies
 */
import woocommerce from '@woocommerce/eslint-config';

export default [
	...woocommerce,
	{
		files: [ 'bin/**/*.js', 'src/**/*.js', 'test/**/*.js' ],
		// Extension webpack configs, CLI and Node tests use CommonJS.
		rules: {
			'@typescript-eslint/no-require-imports': 'off',
		},
	},
];
