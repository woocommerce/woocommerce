/**
 * External dependencies
 */
import { strict as assert } from 'node:assert';
import { spawnSync } from 'node:child_process';
import { join } from 'node:path';
import { test } from 'node:test';

/**
 * Internal dependencies
 */
import { formatReleaseDate, parseReleaseDate } from '../lib/dates';

const runReleaseCommand = ( command: 'beta' | 'dot', args: string[] ) =>
	spawnSync(
		process.execPath,
		[
			'-r',
			'ts-node/register',
			join(
				__dirname,
				'..',
				'commands',
				'release-post',
				`release-post-${ command }.ts`
			),
			...args,
		],
		{
			cwd: join( __dirname, '..' ),
			encoding: 'utf8',
		}
	);

test( 'formats release dates as mm-dd-yyyy', () => {
	assert.equal( formatReleaseDate( new Date( 2026, 7, 19 ) ), '08-19-2026' );
} );

test( 'parses exact calendar dates', () => {
	const releaseDate = parseReleaseDate( '02-29-2028' );

	assert.equal( releaseDate.getFullYear(), 2028 );
	assert.equal( releaseDate.getMonth(), 1 );
	assert.equal( releaseDate.getDate(), 29 );
} );

test( 'rejects impossible calendar dates', () => {
	assert.throws(
		() => parseReleaseDate( '02-30-2026' ),
		/Invalid release date: 02-30-2026/
	);
} );

test( 'rejects extra beta prerelease identifiers', () => {
	const result = runReleaseCommand( 'beta', [
		'11.0.0-beta.1.extra',
		'--releaseDate',
		'08-19-2026',
		'--outputOnly',
	] );

	assert.equal( result.status, 1 );
	assert.match(
		result.stderr,
		/Invalid current version: 11\.0\.0-beta\.1\.extra/
	);
} );

test( 'rejects dot release versions from different release branches', () => {
	const result = runReleaseCommand( 'dot', [
		'11.0.1',
		'10.9.4',
		'--releaseDate',
		'08-19-2026',
		'--outputOnly',
	] );

	assert.equal( result.status, 1 );
	assert.match(
		result.stderr,
		/must have matching major and minor versions/
	);
} );

test( 'rejects dot release ranges that do not move forward', () => {
	const result = runReleaseCommand( 'dot', [
		'11.0.1',
		'11.0.2',
		'--releaseDate',
		'08-19-2026',
		'--outputOnly',
	] );

	assert.equal( result.status, 1 );
	assert.match( result.stderr, /It must precede 11\.0\.1/ );
} );
