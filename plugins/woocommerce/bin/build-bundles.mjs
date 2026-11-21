#!/usr/bin/env node
/**
 * Builds the admin and blocks webpack bundles.
 *
 * A cold build of each one uses about 3 GB of memory. On machines with less
 * than 12 GB they run one after the other, so the build is not killed for
 * running out of memory.
 */
import { spawn } from 'node:child_process';
import os from 'node:os';

const build = ( name ) =>
	new Promise( ( resolve ) => {
		spawn( 'pnpm', [ `--filter=@woocommerce/${ name }`, 'build' ], {
			stdio: 'inherit',
		} ).on( 'exit', ( code ) => resolve( code === 0 ) );
	} );

const results =
	os.totalmem() >= 12 * 1024 ** 3
		? await Promise.all( [
				build( 'block-library' ),
				build( 'admin-library' ),
			] )
		: [ await build( 'block-library' ), await build( 'admin-library' ) ];

process.exitCode = results.every( Boolean ) ? 0 : 1;
