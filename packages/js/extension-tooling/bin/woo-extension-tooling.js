#!/usr/bin/env node

const { spawnSync } = require( 'node:child_process' );
const { constants } = require( 'node:os' );
const path = require( 'node:path' );

const commands = [
	'build-zip',
	'install',
	'makepot',
	'e2e',
	'phpunit',
	'phplint',
	'phpcs',
	'phpcompat',
	'phpcs-baseline',
];
const [ command, ...args ] = process.argv.slice( 2 );

if ( commands.includes( command ) ) {
	const result = spawnSync(
		'bash',
		[ path.join( __dirname, `${ command }.sh` ), ...args ],
		{ stdio: 'inherit' }
	);
	if ( result.error ) {
		process.stderr.write( `${ result.error.message }\n` );
		process.exitCode = 1;
	} else {
		// Node ignores some signals, so report the shell's conventional status.
		process.exitCode =
			result.status ?? 128 + constants.signals[ result.signal ];
	}
} else {
	const output = `Usage: woo-extension-tooling <${ commands.join( ' | ' ) }> [args]\n`;
	if ( ! command || command === '--help' || command === '-h' ) {
		process.stdout.write( output );
	} else {
		process.stderr.write( `Unknown command: ${ command }\n${ output }` );
		process.exitCode = 2;
	}
}
