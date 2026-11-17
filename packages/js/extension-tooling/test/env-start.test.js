const assert = require( 'node:assert/strict' );
const fs = require( 'node:fs' );
const os = require( 'node:os' );
const path = require( 'node:path' );
const { spawnSync } = require( 'node:child_process' );
const { test } = require( 'node:test' );

test( 'Starts wp-env when the test output directory is missing', ( context ) => {
	const directory = fs.mkdtempSync(
		path.join( os.tmpdir(), 'woo-extension-env-' )
	);
	context.after( () =>
		fs.rmSync( directory, { recursive: true, force: true } )
	);

	fs.writeFileSync( path.join( directory, 'package.json' ), '{}' );
	fs.writeFileSync(
		path.join( directory, 'pnpm' ),
		'#!/bin/sh\nprintf "%s\\n" "$*"\n',
		{ mode: 0o755 }
	);

	const result = spawnSync(
		'bash',
		[ path.resolve( __dirname, '../bin/env-start.sh' ), '--update' ],
		{
			cwd: directory,
			env: {
				...process.env,
				PATH: directory + path.delimiter + process.env.PATH,
			},
			encoding: 'utf8',
		}
	);

	assert.ifError( result.error );
	assert.equal( result.status, 0, result.stderr );
	assert.equal( result.stdout.trim(), 'run wp-env:test start --update' );
	assert.ok(
		fs
			.statSync( path.join( directory, 'tests/_output/debug.log' ) )
			.isFile()
	);
} );
