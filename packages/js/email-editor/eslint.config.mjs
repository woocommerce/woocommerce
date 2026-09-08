/**
 * Internal dependencies
 */
import woocommerce from '@woocommerce/eslint-config';
import { coreModules } from '@woocommerce/eslint-config/core-modules.js';

export default [
	...woocommerce,
	{
		settings: {
			'import/core-modules': [
				...coreModules,
				'@wordpress/date',
				'@wordpress/data-controls',
				'@wordpress/keycodes',
				'@wordpress/hooks',
				'@wordpress/preferences',
			],
			'import/resolver': {
				node: {},
				webpack: {},
				typescript: {},
			},
		},
	},
	{
		files: [ 'src/**/*.js', 'src/**/*.ts', 'src/**/*.jsx', 'src/**/*.tsx' ],
		rules: {
			'react/react-in-jsx-scope': 'off',
			'@wordpress/no-unsafe-wp-apis': 'off',
			/*
			 * Translation calls use the `__i18n_text_domain__` identifier so each
			 * consumer of this package can substitute its own text domain at
			 * bundle time (see `development.md`). The default
			 * `@wordpress/i18n-text-domain` rule expects a string literal here, so
			 * disable it for the package source.
			 */
			'@wordpress/i18n-text-domain': 'off',
		},
	},
];
