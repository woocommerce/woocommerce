#!/usr/bin/env node
/**
 * Prints the port this checkout's E2E environment should use.
 *
 * The port has to be *stable* for a given checkout, not merely free: wp-env
 * folds it into its own config checksum, so starting on a different port
 * triggers a destructive reconfigure and a full re-provision. Deriving it from
 * the checkout path keeps it stable across runs while giving each worktree its
 * own port, without a state file to keep in sync.
 *
 * Set WP_ENV_PORT to override.
 */
import { createHash } from 'node:crypto';
import { readFileSync } from 'node:fs';
import { createServer } from 'node:net';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

export const PORT_BASE = 8100;
export const PORT_RANGE = 800;

export function portForPath( path, base = PORT_BASE, range = PORT_RANGE ) {
	const digest = createHash( 'md5' ).update( path ).digest();
	return base + ( digest.readUInt32BE( 0 ) % range );
}

export function isPortFree( port ) {
	return new Promise( ( res ) => {
		const server = createServer();
		server.once( 'error', () => res( false ) );
		server.once( 'listening', () => server.close( () => res( true ) ) );
		server.listen( port, '127.0.0.1' );
	} );
}

export function configuredPort( pluginRoot ) {
	const config = JSON.parse(
		readFileSync( join( pluginRoot, '.wp-env.e2e.json' ), 'utf8' )
	);
	return config.port;
}

export function resolvePort( env, pluginRoot ) {
	const override = Number( env.WP_ENV_PORT );
	if ( Number.isInteger( override ) && override > 0 ) {
		return override;
	}

	// Every CI job gets its own runner, so there is nothing to avoid colliding
	// with, and the suites that read the port from a *separate* process — k6 and
	// the metrics run — would not see a derived one. Stick to the configured port.
	if ( env.CI ) {
		return configuredPort( pluginRoot );
	}

	return portForPath( pluginRoot );
}

/* istanbul ignore next -- entry point */
if ( process.argv[ 1 ] === fileURLToPath( import.meta.url ) ) {
	// plugins/woocommerce, the directory wp-env is invoked from.
	const pluginRoot = resolve(
		dirname( fileURLToPath( import.meta.url ) ),
		'..',
		'..',
		'..'
	);
	const port = resolvePort( process.env, pluginRoot );

	// Advisory only: a busy port is almost always this checkout's own running
	// environment, which is exactly the one we want to reuse. Moving to a
	// different port to avoid a stranger would force a re-provision, so leave
	// that call to the developer via WP_ENV_PORT.
	if ( ! ( await isPortFree( port ) ) ) {
		process.stderr.write(
			`Port ${ port } is in use. If that is not this checkout's E2E environment, ` +
				`set WP_ENV_PORT to something else.\n`
		);
	}

	process.stdout.write( String( port ) );
}
