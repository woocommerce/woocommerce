/**
 * Runs `playwright test`, replacing `--shard=i/n` with a duration-balanced file list.
 *
 * Only for projects listed in spec-durations.json and only when CI_DURATION_SHARDS=1;
 * otherwise the arguments go to Playwright unchanged. Files missing from the JSON weigh
 * the median. Refresh the JSON with refresh-spec-durations.mjs.
 */

/**
 * External dependencies
 */
import { spawnSync } from 'node:child_process';
import { mkdtempSync, readFileSync, rmSync, writeFileSync } from 'node:fs';
import { createRequire } from 'node:module';
import { constants, tmpdir } from 'node:os';
import { join } from 'node:path';

export const DURATIONS_FILE = new URL(
	'../spec-durations.json',
	import.meta.url
);

/**
 * Splits `--shard` out of the arguments; the last one wins, as in Playwright, which the
 * CI rerun flow relies on when it appends `--last-failed --shard=1/1`. The project is
 * only read from a single `--project=<name>`.
 *
 * @param {string[]} args CLI arguments.
 * @return {{shard: {current: number, total: number}|null, project: string|null, rest: string[]}} Parsed arguments.
 */
export function parseArgs( args ) {
	let shard = null;
	const projects = [];
	const rest = [];
	for ( let i = 0; i < args.length; i++ ) {
		const [ flag, inline ] = args[ i ].split( /=(.*)/s );
		if ( flag === '--shard' ) {
			const [ current, total ] = ( inline ?? args[ ++i ] ?? '' )
				.split( '/' )
				.map( Number );
			shard = { current, total };
			continue;
		}
		if ( flag === '--project' ) {
			projects.push( inline );
		}
		rest.push( args[ i ] );
	}
	return {
		shard,
		project: projects.length === 1 ? projects[ 0 ] ?? null : null,
		rest,
	};
}

function median( values ) {
	const sorted = [ ...values ].sort( ( a, b ) => a - b );
	const mid = Math.floor( sorted.length / 2 );
	return sorted.length % 2
		? sorted[ mid ]
		: ( sorted[ mid - 1 ] + sorted[ mid ] ) / 2;
}

/**
 * Packs files into `total` bins with the longest-processing-time rule. Deterministic:
 * ties sort by file name and go to the lowest bin index.
 *
 * @param {string[]}               files     Spec files, relative to the config's rootDir.
 * @param {Record<string, number>} durations Known durations in ms.
 * @param {number}                 total     Number of shards.
 * @return {{files: string[], load: number}[]} One bin per shard.
 */
export function packByDuration( files, durations, total ) {
	const known = Object.values( durations );
	const fallback = known.length ? median( known ) : 1;
	const weighted = [ ...new Set( files ) ]
		.map( ( file ) => [ file, durations[ file ] ?? fallback ] )
		.sort( ( a, b ) => b[ 1 ] - a[ 1 ] || ( a[ 0 ] < b[ 0 ] ? -1 : 1 ) );
	const bins = Array.from( { length: total }, () => ( {
		files: [],
		load: 0,
	} ) );
	for ( const [ file, weight ] of weighted ) {
		const bin = bins.reduce( ( min, candidate ) =>
			candidate.load < min.load ? candidate : min
		);
		bin.files.push( file );
		bin.load += weight;
	}
	return bins;
}

function playwright( args, options = {} ) {
	const cli = createRequire( import.meta.url ).resolve(
		'@playwright/test/cli'
	);
	const { status, signal, error } = spawnSync(
		process.execPath,
		[ cli, 'test', ...args ],
		{ stdio: 'inherit', ...options }
	);
	if ( error ) {
		throw error;
	}
	return signal ? 128 + constants.signals[ signal ] : status;
}

function listFiles( args, project, dir ) {
	const outputFile = join( dir, 'list.json' );
	// Playwright prints the whole list to stdout when the JSON goes to a file.
	const status = playwright( [ ...args, '--list', '--reporter=json' ], {
		env: { ...process.env, PLAYWRIGHT_JSON_OUTPUT_FILE: outputFile },
		stdio: [ 'inherit', 'ignore', 'inherit' ],
	} );
	if ( status !== 0 ) {
		throw new Error( `Listing tests failed with exit code ${ status }` );
	}
	const files = new Set();
	const walk = ( suite ) => {
		for ( const spec of suite.specs ?? [] ) {
			if ( spec.tests.some( ( t ) => t.projectName === project ) ) {
				files.add( spec.file );
			}
		}
		( suite.suites ?? [] ).forEach( walk );
	};
	JSON.parse( readFileSync( outputFile, 'utf8' ) ).suites.forEach( walk );
	return [ ...files ];
}

function runShard( shard, project, rest, durations ) {
	const dir = mkdtempSync( join( tmpdir(), 'pw-shard-' ) );
	try {
		const bins = packByDuration(
			listFiles( rest, project, dir ),
			durations,
			shard.total
		);
		const mine = bins[ shard.current - 1 ];
		const loads = bins.map( ( bin ) => Math.round( bin.load / 1000 ) );
		console.log(
			`Duration shard ${ shard.current }/${
				shard.total
			} of ${ project }, est. seconds per shard [${ loads }]:\n  ${ mine.files.join(
				'\n  '
			) }`
		);
		if ( ! mine.files.length ) {
			return 0;
		}
		const testList = join( dir, 'test-list.txt' );
		writeFileSync(
			testList,
			mine.files
				.map( ( file ) => `[${ project }] > ${ file }` )
				.join( '\n' )
		);
		return playwright( [ ...rest, `--test-list=${ testList }` ] );
	} finally {
		rmSync( dir, { recursive: true, force: true } );
	}
}

function main() {
	const args = process.argv.slice( 2 );
	if ( process.env.CI_DURATION_SHARDS !== '1' ) {
		return playwright( args );
	}
	const { shard, project, rest } = parseArgs( args );
	const durations = JSON.parse( readFileSync( DURATIONS_FILE, 'utf8' ) )[
		project
	];
	const usable =
		shard?.total > 1 &&
		shard.current >= 1 &&
		shard.current <= shard.total &&
		Object.keys( durations ?? {} ).length > 0;
	return usable
		? runShard( shard, project, rest, durations )
		: playwright( args );
}

if ( import.meta.main ) {
	process.exit( main() );
}
