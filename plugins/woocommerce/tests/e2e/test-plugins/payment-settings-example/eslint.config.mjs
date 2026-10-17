/**
 * External dependencies
 */
import woocommerce from '@woocommerce/eslint-config';

export default [
	...woocommerce,
	{
		settings: {
			// wp-build supplies this script module through WordPress's import map.
			'import/core-modules': [ '@woocommerce/settings-screens' ],
		},
	},
];
