/**
 * External dependencies
 */
import tseslint from '@typescript-eslint/eslint-plugin';

/**
 * Internal dependencies
 */
import woocommerce from '@woocommerce/eslint-config';

/*
 * Routes are new code, so they start on stricter settings than the rest of the
 * monorepo: the preset's relaxed warnings are errors here, and TypeScript gets
 * typescript-eslint's strict and stylistic type-checked rules.
 */
const escalateWarnings = ( config ) =>
	config.rules
		? {
				...config,
				rules: Object.fromEntries(
					Object.entries( config.rules ).map( ( [ rule, value ] ) => [
						rule,
						value === 'warn' ? 'error' : value,
					] )
				),
			}
		: config;

// Only the rule sets: the preset already registers the plugin and parser.
const rulesOf = ( configs ) =>
	Object.assign( {}, ...configs.map( ( config ) => config.rules ?? {} ) );

export default [
	{ ignores: [ 'packages/*/build-module/', 'packages/*/build-style/' ] },
	...woocommerce.map( escalateWarnings ),
	{
		files: [ '**/*.ts', '**/*.tsx' ],
		languageOptions: {
			parserOptions: {
				projectService: true,
				tsconfigRootDir: import.meta.dirname,
			},
		},
		rules: {
			...rulesOf( tseslint.configs[ 'flat/strict-type-checked' ] ),
			...rulesOf( tseslint.configs[ 'flat/stylistic-type-checked' ] ),
			// The router expects notFound() and redirect() results to be thrown.
			'@typescript-eslint/only-throw-error': [
				'error',
				{
					allow: [
						{
							from: 'package',
							package: '@tanstack/router-core',
							name: [ 'NotFoundError', 'Redirect' ],
						},
					],
				},
			],
		},
	},
	{
		// Routes aren't packages, so check imports against this package's dependencies.
		rules: {
			'import/no-extraneous-dependencies': [
				'error',
				{ packageDir: import.meta.dirname },
			],
		},
	},
	{
		linterOptions: {
			reportUnusedDisableDirectives: 'error',
		},
	},
];
