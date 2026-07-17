#!/usr/bin/env node
/**
 * Single-command orchestrator for the WooCommerce Core E2E environment.
 * Wraps `wp-env --config .wp-env.e2e.json` with per-worktree ports, a
 * staleness hash, and a snapshot-based auto-reset. See TESTOPS-196.
 */
import { createHash } from 'node:crypto';
import { readFileSync, writeFileSync, mkdirSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { join, dirname, resolve as resolvePath } from 'node:path';
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

// wp-env vars that are allowed to reach wp-env (everything else `WP_ENV_*` is
// stripped so a stray var can't flip wp-env's own config checksum out from under
// our staleness hash). `WP_ENV_HOME` is included because it relocates the whole
// instance directory rather than feeding the config checksum: stripping it would
// desync run-env from the sibling `env:e2e:stop`/`:destroy` scripts (which honor
// it), leaving containers that can't be stopped. It is folded into our hash by
// `computeHash`, so changing it correctly forces a rebuild.
export const ALLOWED_WP_ENV_VARS = [
	'WP_ENV_CORE',
	'WP_ENV_PHP_VERSION',
	'WP_ENV_PORT',
	'WP_ENV_HOME',
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
	// A corrupt/old state file with a missing or non-numeric snapshotCreatedAt
	// makes the age comparison NaN (always false); treat it as stale rather than
	// silently reusing an unknowable-age snapshot.
	if ( ! Number.isFinite( state.snapshotCreatedAt ) ) return 'rebuild';
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
	// `hash` is interpolated into the shell command below; every caller passes a
	// computeHash() digest, so assert that invariant locally rather than trusting
	// the contract across files.
	if ( ! /^[0-9a-f]{32}$/.test( hash ) ) {
		throw new Error( `writeSentinel: refusing to write non-md5 hash "${ hash }"` );
	}
	// SNAP_DIR lives under the web root so the sentinel is HTTP-fetchable (that is
	// how isOurInstance() checks port ownership). The DB dump and uploads archive
	// captured alongside it must NOT be served, so drop an .htaccess that denies
	// the directory and re-allows only `sentinel`. It is written before
	// captureSnapshot() runs and lives on the same wiped mount, so the exposure
	// window never opens and the snapshot==sentinel divergence coupling is intact.
	const htaccess = 'Require all denied\\n<Files sentinel>\\nRequire all granted\\n</Files>\\n';
	return wpCli(
		`mkdir -p ${ SNAP_DIR } && ` +
			`printf '%s' '${ hash }' > ${ SNAP_DIR }/sentinel && ` +
			`printf '${ htaccess }' > ${ SNAP_DIR }/.htaccess`,
		{ env, capture: false }
	);
}

export async function readSentinel( { env } ) {
	return ( await wpCli( `cat ${ SNAP_DIR }/sentinel 2>/dev/null || true`, { env } ) ).trim();
}

export async function wpCoreVersion( { env } ) {
	return ( await wpCli( `wp core version`, { env } ) ).trim();
}

export function formatRunnerEnv( port ) {
	// BASE_URL is what playwright.config.ts and most specs read. WP_ENV_TESTS_PORT
	// is also emitted because a few specs build permalink literals directly from
	// it (e.g. `http://localhost:${WP_ENV_TESTS_PORT || '8086'}/...`) and would
	// otherwise hard-code 8086 and never match under a random per-worktree port.
	return (
		`BASE_URL=http://localhost:${ port }\n` +
		`WP_ENV_TESTS_PORT=${ port }\n`
	);
}

export function writeRunnerEnv( stateDir, port ) {
	mkdirSync( stateDir, { recursive: true } );
	writeFileSync( join( stateDir, 'runner.env' ), formatRunnerEnv( port ) );
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
	const pluginRoot = resolvePath( dirname( fileURLToPath( import.meta.url ) ), '..', '..', '..' );
	process.chdir( pluginRoot );

	const { rebuild: rebuildFlag, passthrough } = parseArgs( process.argv.slice( 2 ) );

	// CI: lean provision-once pass-through; preserve today's behavior exactly.
	if ( isCi( process.env ) ) {
		const env = sanitizeEnv( { ...process.env } );
		await wpEnv( [ 'start', '--update', ...passthrough ], { env } );
		return;
	}

	// Only `--rebuild` is meaningful in dev mode; anything else (e.g. a stray
	// `--debug` that only CI mode forwards) is silently inert, so say so.
	if ( passthrough.length > 0 ) {
		console.warn(
			`run-env: ignoring argument(s) not used in dev mode: ${ passthrough.join( ' ' ) }`
		);
	}

	const stateDir = 'tests/e2e/.env-state';
	const state = readState( stateDir );
	// The optional override file may be absent; the primary config must exist —
	// fail fast with a clear error instead of hashing '' and failing deeper in
	// wp-env with a worse message.
	const readOr = ( p ) => {
		try {
			return readFileSync( p, 'utf8' );
		} catch {
			return '';
		}
	};
	const wcVersion = readWcVersion( readFileSync( 'woocommerce.php', 'utf8' ) );

	let port = state?.port ?? ( await probeFreePort() );
	let foreignPort = false;
	if ( state?.port && ! ( await isPortFree( port ) ) ) {
		const ours = await isOurInstance( `http://localhost:${ port }`, state.hash );
		if ( ! ours ) {
			port = await probeFreePort();
			foreignPort = true;
		}
	}

	const env = sanitizeEnv( { ...process.env, WP_ENV_PORT: String( port ) } );
	const hash = computeHash( {
		configText: readFileSync( '.wp-env.e2e.json', 'utf8' ),
		overrideText: readOr( '.wp-env.e2e.override.json' ),
		setupScriptText: readOr( 'tests/e2e/bin/test-env-setup.sh' ),
		allowlistEnv: env,
		wcVersion,
	} );

	writeRunnerEnv( stateDir, port );

	const action = decide( {
		state,
		currentHash: hash,
		nowMs: Date.now(),
		maxAgeMs: MAX_AGE_MS,
		forceRebuild: rebuildFlag || foreignPort,
	} );

	if ( action === 'fresh' ) {
		const result = await fresh( { env, hash, wpVersion: state.wpVersion } );
		if ( result === 'restored' ) {
			console.log( `E2E env ready (reused) on http://localhost:${ port }` );
			return;
		}
		console.log( 'Env diverged from snapshot; rebuilding…' );
	}

	await rebuild( { env, hash, port, stateDir } );
	console.log( `E2E env ready (rebuilt) on http://localhost:${ port }` );
}

// Run main() only when executed directly, not when imported by tests.
if ( process.argv[ 1 ] === fileURLToPath( import.meta.url ) ) {
	main().catch( ( err ) => {
		console.error( err );
		process.exit( 1 );
	} );
}
