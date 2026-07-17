#!/usr/bin/env node
/**
 * Single-command orchestrator for the WooCommerce Core E2E environment.
 * Wraps `wp-env --config .wp-env.e2e.json` with per-worktree ports, a
 * staleness hash, and a snapshot-based auto-reset. See TESTOPS-196.
 */
import { createHash } from 'node:crypto';
import { readFileSync, writeFileSync, mkdirSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { join } from 'node:path';

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

export function readWcVersion( pluginPhpText ) {
	const match = pluginPhpText.match( /^\s*\*?\s*Version:\s*(\S+)/m );
	if ( ! match ) {
		throw new Error( 'Could not read Version header from woocommerce.php' );
	}
	return match[ 1 ];
}

export function computeHash( {
	configText,
	overrideText,
	setupScriptText,
	allowlistEnv,
	wcVersion,
} ) {
	const allow = {};
	for ( const key of ALLOWED_WP_ENV_VARS ) {
		if ( allowlistEnv[ key ] !== undefined ) {
			allow[ key ] = allowlistEnv[ key ];
		}
	}
	const canonical = JSON.stringify( {
		configText,
		overrideText,
		setupScriptText,
		allow,
		wcVersion,
	} );
	return createHash( 'md5' ).update( canonical ).digest( 'hex' );
}

export const MAX_AGE_MS = 7 * 24 * 60 * 60 * 1000;

export function readState( stateDir ) {
	try {
		return JSON.parse( readFileSync( join( stateDir, 'instance.json' ), 'utf8' ) );
	} catch {
		return null;
	}
}

export function writeState( stateDir, state ) {
	mkdirSync( stateDir, { recursive: true } );
	writeFileSync(
		join( stateDir, 'instance.json' ),
		JSON.stringify( state, null, 2 ) + '\n'
	);
}

export function decide( { state, currentHash, nowMs, maxAgeMs, forceRebuild } ) {
	if ( forceRebuild ) return 'rebuild';
	if ( ! state ) return 'rebuild';
	if ( state.hash !== currentHash ) return 'rebuild';
	if ( nowMs - state.snapshotCreatedAt > maxAgeMs ) return 'rebuild';
	return 'fresh';
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
