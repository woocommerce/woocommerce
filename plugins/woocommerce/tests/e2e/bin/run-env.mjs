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
import { createServer as createNetServer } from 'node:net';
import { get as httpGet } from 'node:http';
import { spawn } from 'node:child_process';

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

export function probeFreePort() {
	return new Promise( ( resolve, reject ) => {
		const srv = createNetServer();
		srv.on( 'error', reject );
		srv.listen( 0, () => {
			const { port } = srv.address();
			srv.close( () => resolve( port ) );
		} );
	} );
}

export function isPortFree( port ) {
	return new Promise( ( resolve ) => {
		const srv = createNetServer();
		srv.once( 'error', () => resolve( false ) );
		srv.listen( port, () => srv.close( () => resolve( true ) ) );
	} );
}

export function isOurInstance( baseUrl, expectedHash ) {
	return new Promise( ( resolve ) => {
		const req = httpGet( `${ baseUrl }/.e2e-snapshot/sentinel`, ( res ) => {
			let body = '';
			res.on( 'data', ( c ) => ( body += c ) );
			res.on( 'end', () => resolve( body.trim() === expectedHash ) );
		} );
		req.on( 'error', () => resolve( false ) );
		req.setTimeout( 3000, () => {
			req.destroy();
			resolve( false );
		} );
	} );
}

const E2E_CONFIG = '.wp-env.e2e.json';
const SNAP_DIR = '/var/www/html/.e2e-snapshot';

function run( cmd, args, { env, capture = false } ) {
	return new Promise( ( resolve, reject ) => {
		const child = spawn( cmd, args, {
			env,
			stdio: capture ? [ 'inherit', 'pipe', 'inherit' ] : 'inherit',
		} );
		let out = '';
		if ( capture ) child.stdout.on( 'data', ( c ) => ( out += c ) );
		child.on( 'error', reject );
		child.on( 'close', ( code ) =>
			code === 0
				? resolve( out )
				: reject( new Error( `${ cmd } ${ args.join( ' ' ) } exited ${ code }` ) )
		);
	} );
}

export function wpEnv( args, { env } ) {
	return run( 'pnpm', [ 'exec', 'wp-env', '--config', E2E_CONFIG, ...args ], { env } );
}

export function wpCli( shScript, { env, capture = true } ) {
	return run(
		'pnpm',
		[ 'exec', 'wp-env', '--config', E2E_CONFIG, 'run', 'cli', 'sh', '-c', shScript ],
		{ env, capture }
	);
}

export function captureSnapshot( { env } ) {
	return wpCli(
		`mkdir -p ${ SNAP_DIR } && ` +
			`wp db export ${ SNAP_DIR }/db.sql && ` +
			`tar -C /var/www/html/wp-content -cf ${ SNAP_DIR }/uploads.tar uploads`,
		{ env, capture: false }
	);
}

export function restoreSnapshot( { env } ) {
	return wpCli(
		`wp db reset --yes && wp db import ${ SNAP_DIR }/db.sql && ` +
			`rm -rf /var/www/html/wp-content/uploads && ` +
			`tar -C /var/www/html/wp-content -xf ${ SNAP_DIR }/uploads.tar`,
		{ env, capture: false }
	);
}

export function writeSentinel( hash, { env } ) {
	return wpCli( `mkdir -p ${ SNAP_DIR } && printf '%s' '${ hash }' > ${ SNAP_DIR }/sentinel`, {
		env,
		capture: false,
	} );
}

export async function readSentinel( { env } ) {
	return ( await wpCli( `cat ${ SNAP_DIR }/sentinel 2>/dev/null || true`, { env } ) ).trim();
}

export async function wpCoreVersion( { env } ) {
	return ( await wpCli( `wp core version`, { env } ) ).trim();
}

export async function rebuild( { env, hash, port, stateDir } ) {
	// start writes docker files / extracts WP and self-heals on wp-env's own
	// checksum change; clean alone would hard-exit on a first-ever run.
	await wpEnv( [ 'start', '--scripts=false' ], { env } );
	const wpVersion = await wpCoreVersion( { env } );
	// clean resets the DB then re-provisions via the afterClean lifecycle hook.
	await wpEnv( [ 'clean', 'development' ], { env } );

	// Post-clean sanity check: clean swallows reset failures (docker/index.js:
	// 516-521), so confirm BOTH re-provisioning (themes on disk) AND that the DB
	// reset happened before freezing the baseline. The freshly-provisioned
	// baseline holds the 3 seeded images plus WooCommerce's own placeholder
	// attachment (= 4). A silently-failed reset re-runs the unconditional
	// `wp media import` on top of the stale DB, doubling the images (>=7), so a
	// 3..5 window confirms provisioning ran without gross accumulation.
	const themeCount = Number(
		( await wpCli( `wp theme list --field=name | wc -l`, { env } ) ).trim()
	);
	const attachmentCount = Number(
		( await wpCli( `wp post list --post_type=attachment --format=count`, { env } ) ).trim()
	);
	// `themeCount` is piped through `wc -l` so it degrades to 0 (which trips the
	// check) if the wp-cli call misbehaves; the raw `--format=count` output for
	// attachments has no such floor, so guard against a non-numeric parse (a
	// stray notice on stdout → NaN) explicitly — NaN comparisons are all false
	// and would otherwise let a silently-failed reset slip through.
	if (
		themeCount < 3 ||
		! Number.isFinite( attachmentCount ) ||
		attachmentCount < 3 ||
		attachmentCount > 5
	) {
		throw new Error(
			`Provisioning sanity check failed: themes=${ themeCount } (need >=3), ` +
				`attachments=${ attachmentCount } (expected ~4: 3 seeded images + WC placeholder)`
		);
	}

	await writeSentinel( hash, { env } );
	await captureSnapshot( { env } );
	writeState( stateDir, { hash, port, wpVersion, snapshotCreatedAt: Date.now() } );
}

export async function fresh( { env, hash, wpVersion } ) {
	await wpEnv( [ 'start', '--scripts=false' ], { env } );

	const sentinel = await readSentinel( { env } );
	const liveWp = await wpCoreVersion( { env } );
	if ( sentinel !== hash || liveWp !== wpVersion ) {
		return 'diverged';
	}

	await restoreSnapshot( { env } );
	return 'restored';
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
