import { cpSync, rmSync, watch } from 'node:fs';
import { spawnSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';

const packageDirectory = fileURLToPath( new URL( '.', import.meta.url ) );
const buildDirectory = new URL( './build/', import.meta.url );
const coreDirectory = new URL(
	'../../../plugins/woocommerce/assets/client/settings-dataform/',
	import.meta.url
);
const watchMode = process.argv.includes( '--watch' );

function build() {
	rmSync( buildDirectory, { recursive: true, force: true } );
	const result = spawnSync(
		process.execPath,
		[ fileURLToPath( import.meta.resolve( '@wordpress/build' ) ) ],
		{ cwd: packageDirectory, stdio: 'inherit' }
	);

	if ( result.error ) {
		throw result.error;
	}
	if ( result.status !== 0 ) {
		if ( ! watchMode ) {
			process.exitCode = result.status || 1;
		}
		return;
	}

	rmSync( coreDirectory, { recursive: true, force: true } );
	cpSync( buildDirectory, coreDirectory, { recursive: true } );
	process.stdout.write(
		'Settings DataForm assets copied to WooCommerce Core.\n'
	);
}

build();

if ( watchMode ) {
	let timer;
	watch( packageDirectory, { recursive: true }, ( event, filename ) => {
		const file = filename?.replaceAll( '\\', '/' );
		if (
			! file ||
			/(^|\/)(node_modules|build|build-module|build-style)(\/|$)/.test(
				file
			) ||
			! /^(packages\/|routes\/|package\.json$|tsconfig\.json$)/.test(
				file
			)
		) {
			return;
		}
		clearTimeout( timer );
		timer = setTimeout( build, 150 );
	} );
	process.stdout.write( 'Watching Settings DataForm sources...\n' );
}
