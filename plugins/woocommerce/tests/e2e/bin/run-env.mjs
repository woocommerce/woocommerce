#!/usr/bin/env node
/**
 * Single-command orchestrator for the WooCommerce Core E2E environment.
 * Wraps `wp-env --config .wp-env.e2e.json` with per-worktree ports, a
 * staleness hash, and a snapshot-based auto-reset. See TESTOPS-196.
 */
import { fileURLToPath } from 'node:url';

export function parseArgs( argv ) {
	const rebuild = argv.includes( '--rebuild' );
	const passthrough = argv.filter( ( a ) => a !== '--rebuild' );
	return { rebuild, passthrough };
}

export function isCi( env ) {
	return typeof env.CI === 'string' && env.CI.length > 0;
}

export const ALLOWED_WP_ENV_VARS = [
	'WP_ENV_CORE',
	'WP_ENV_PHP_VERSION',
	'WP_ENV_PORT',
];

export function sanitizeEnv( env ) {
	const out = {};
	for ( const [ key, value ] of Object.entries( env ) ) {
		if ( key.startsWith( 'WP_ENV_' ) && ! ALLOWED_WP_ENV_VARS.includes( key ) ) {
			continue;
		}
		out[ key ] = value;
	}
	return out;
}

async function main() {
	// Filled in by later tasks.
}

// Run main() only when executed directly, not when imported by tests.
if ( process.argv[ 1 ] === fileURLToPath( import.meta.url ) ) {
	main().catch( ( err ) => {
		console.error( err );
		process.exit( 1 );
	} );
}
