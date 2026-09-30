/**
 * Internal dependencies
 */
import woocommerce from '@woocommerce/eslint-config';

/*
 * Routes match Gutenberg's routes: the preset's rules at full strength, so the
 * warnings it relaxed for the rest of the monorepo are errors here.
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

export default [
	...woocommerce.map( escalateWarnings ),
	{
		linterOptions: {
			reportUnusedDisableDirectives: 'error',
		},
	},
];
