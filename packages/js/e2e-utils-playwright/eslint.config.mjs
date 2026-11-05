/**
 * Internal dependencies
 */
import woocommerce from '@woocommerce/eslint-config';

/*
 * The eslintrc this replaces set no `extends` and no `root`, so it inherited the
 * repo root config by cascade. Flat config does not cascade, so spread the
 * shared config explicitly.
 */
export default [
	...woocommerce,
	{
		rules: {
			'no-console': 'off',
		},
	},
];
