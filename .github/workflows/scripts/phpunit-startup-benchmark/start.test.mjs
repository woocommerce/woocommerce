import assert from 'node:assert/strict';
import { test } from 'node:test';
import { mkdtempSync, mkdirSync, writeFileSync, rmSync } from 'node:fs';
import { tmpdir } from 'node:os';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { spawnSync } from 'node:child_process';

test( 'launcher makes package executables available to lifecycle subprocesses', ( t ) => {
	const root = mkdtempSync( path.join( tmpdir(), 'wc-launcher-' ) );
	t.after( () => rmSync( root, { recursive: true, force: true } ) );
	const put = ( name, contents, mode ) => {
		const filename = path.join( root, name );
		mkdirSync( path.dirname( filename ), { recursive: true } );
		writeFileSync( filename, contents, { mode } );
	};
	const plugin = 'plugins/woocommerce';
	const env = `${ plugin }/node_modules/@wordpress/env`;
	put( `${ plugin }/package.json`, '{}' );
	put( `${ env }/package.json`, '{}' );
	put(
		`${ env }/lib/runtime/docker/docker-config.js`,
		'module.exports = {writeDockerFiles: async () => {}};'
	);
	put( `${ env }/node_modules/js-yaml/index.js`, 'module.exports = {};' );
	// Stand in only for the external CLI; exercise the real launcher and OS PATH lookup.
	put(
		`${ env }/bin/wp-env`,
		'const r = require("node:child_process").spawnSync("wc-lifecycle-probe", [], {encoding:"utf8"}); if (r.error) throw r.error; process.stdout.write(r.stdout); process.exitCode=r.status;'
	);
	put(
		`${ plugin }/node_modules/.bin/wc-lifecycle-probe`,
		'#!/bin/sh\necho fixture-ready\n',
		0o755
	);
	put( 'pnpm-lock.yaml', 'lockfileVersion: 9.0\n' );
	put(
		'inputs.json',
		'{"wordpress":"https://wordpress.org/wordpress-7.1.4.zip","themes":[],"images":{}}'
	);
	const result = spawnSync(
		process.execPath,
		[ fileURLToPath( new URL( './start.mjs', import.meta.url ) ) ],
		{
			cwd: root,
			encoding: 'utf8',
			env: {
				...process.env,
				CI: 'true',
				PATH: '/usr/bin:/bin',
				BENCH_VARIANT: 'baseline',
				BENCH_INPUTS: path.join( root, 'inputs.json' ),
				BENCH_RESULTS: path.join( root, 'results' ),
			},
		}
	);
	assert.equal( result.status, 0, result.stderr );
	assert.equal( result.stdout.trim(), 'fixture-ready' );
} );
