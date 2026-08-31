/**
 * External dependencies
 */
import { globalIgnores } from 'eslint/config';

/**
 * Internal dependencies
 */
import woocommerce from '@woocommerce/eslint-config';
import { coreModules } from '@woocommerce/eslint-config/core-modules.js';

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
				...coreModules,
				'@wordpress/block-library',
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
