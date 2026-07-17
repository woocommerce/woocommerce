/**
 * External dependencies
 */
import { globalIgnores } from 'eslint/config';

/**
 * Internal dependencies
 */
import woocommerce from '@woocommerce/eslint-config';

export default [
	// node_modules is ignored by default. `api` has its own config and command.
	globalIgnores( [
		'bin/*',
		'!bin/generate-docs',
		'build',
		'build-module',
		'build-types',
		'coverage',
		'languages',
		'vendor',
		'legacy',
		'tests/e2e',
		'api',
	] ),
	/*
	 * The eslintrc registered the `import` plugin itself. It must not:
	 * eslint-plugin-import has no ESLint v10 support, and the shared config
	 * already provides the fixupPluginRules-wrapped instance WordPress registers.
	 * Registering a second copy is both fatal at rule-run time and a
	 * "Cannot redefine plugin" hazard.
	 */
	...woocommerce,
	{
		settings: {
			'import/core-modules': [
				'@woocommerce/admin-layout',
				'@woocommerce/components',
				'@woocommerce/customer-effort-score',
				'@woocommerce/currency',
				'@woocommerce/csv-export',
				'@woocommerce/data',
				'@woocommerce/date',
				'@woocommerce/explat',
				'@woocommerce/internal-js-tests',
				'@woocommerce/navigation',
				'@woocommerce/number',
				'@woocommerce/onboarding',
				'@woocommerce/product-editor',
				'@woocommerce/settings',
				'@woocommerce/tracks',
				'@woocommerce/experimental',
				'@wordpress/components',
				'@wordpress/core-data',
				'@wordpress/element',
				'@wordpress/blocks',
				'@wordpress/block-editor',
				'@wordpress/block-library',
				'@wordpress/notices',
				'dompurify',
				'@react-spring/web',
				'react-router-dom',
				'redux',
				'xstate',
				'xstate5',
			],
			'import/resolver': {
				node: {},
				webpack: {},
				typescript: {
					project: [
						'plugins/woocommerce/client/admin/tsconfig.json',
					],
				},
			},
		},
	},
	{
		files: [ 'client/**/*.js', 'client/**/*.jsx', 'client/**/*.tsx' ],
		rules: {
			'react/react-in-jsx-scope': 'off',
		},
	},
];
