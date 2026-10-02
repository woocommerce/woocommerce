/**
 * Rebuilds spec-durations.json from downloaded CI report artifacts (ctrf-report-*.json).
 *
 * Usage: node tests/e2e/bin/refresh-spec-durations.mjs <report-dir>...
 * Each directory is one CI run (e.g. `gh run download <id> -n blocks-e2e-report-attempt-1`);
 * per-file totals are averaged across runs. Only projects already in the JSON are refreshed,
 * so add `"<project>": {}` to start tracking one. Entries for deleted spec files are dropped.
 */

/**
 * External dependencies
 */
import { existsSync, readdirSync, readFileSync, writeFileSync } from 'node:fs';
import { join } from 'node:path';
import { fileURLToPath } from 'node:url';

/**
 * Internal dependencies
 */
import { DURATIONS_FILE } from './playwright-duration-shard.mjs';

const TESTS_ROOT = fileURLToPath( new URL( '../tests', import.meta.url ) );

function runTotals( dir ) {
	const totals = {};
	const reports = readdirSync( dir, { recursive: true } ).filter( ( file ) =>
		/(^|\/)ctrf-report-.*\.json$/.test( file )
	);
	for ( const report of reports ) {
		const { tests } = JSON.parse(
			readFileSync( join( dir, report ), 'utf8' )
		).results;
		for ( const { suite, duration } of tests ) {
			const [ project, file ] = suite.split( ' > ' );
			totals[ project ] ??= {};
			totals[ project ][ file ] =
				( totals[ project ][ file ] ?? 0 ) + duration;
		}
	}
	return totals;
}

const dirs = process.argv.slice( 2 );
if ( ! dirs.length ) {
	console.error( 'Usage: refresh-spec-durations.mjs <report-dir>...' );
	process.exit( 1 );
}

const runs = dirs.map( runTotals );
const current = JSON.parse( readFileSync( DURATIONS_FILE, 'utf8' ) );
const next = {};
for ( const project of Object.keys( current ).sort() ) {
	const measured = {};
	for ( const run of runs ) {
		for ( const [ file, ms ] of Object.entries( run[ project ] ?? {} ) ) {
			( measured[ file ] ??= [] ).push( ms );
		}
	}
	const merged = { ...current[ project ] };
	for ( const [ file, samples ] of Object.entries( measured ) ) {
		merged[ file ] = Math.round(
			samples.reduce( ( a, b ) => a + b, 0 ) / samples.length
		);
	}
	next[ project ] = Object.fromEntries(
		Object.entries( merged )
			.filter( ( [ file ] ) => existsSync( join( TESTS_ROOT, file ) ) )
			.sort( ( [ a ], [ b ] ) => ( a < b ? -1 : 1 ) )
	);
	console.log(
		`${ project }: ${ Object.keys( measured ).length } measured, ${
			Object.keys( next[ project ] ).length
		} kept`
	);
}
writeFileSync( DURATIONS_FILE, JSON.stringify( next, null, '\t' ) + '\n' );
