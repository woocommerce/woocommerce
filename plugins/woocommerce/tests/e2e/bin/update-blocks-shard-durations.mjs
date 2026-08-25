/**
 * Refreshes tests/e2e/blocks-shard-durations.json from CTRF reports.
 *
 * The Blocks e2e jobs upload a `blocks-e2e-report` artifact per run containing
 * `ctrf-report-*.json` files, one per shard, each carrying a per-test duration
 * and file path. Point this script at a directory of unpacked artifacts and it
 * rewrites the manifest with the median duration per spec file.
 *
 * Merge several runs. A single artifact only covers the shards that uploaded
 * into it, so one run on its own will miss files and understate the suite.
 *
 * Usage:
 *   node tests/e2e/bin/update-blocks-shard-durations.mjs <dir> [<dir> ...]
 */

/**
 * External dependencies
 */
import { readdirSync, readFileSync, writeFileSync } from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const PLUGIN_ROOT = path.resolve(
	path.dirname( fileURLToPath( import.meta.url ) ),
	'../../..'
);
const DURATIONS_FILE = path.join(
	PLUGIN_ROOT,
	'tests/e2e/blocks-shard-durations.json'
);
const SPEC_PATTERN = /\/plugins\/woocommerce\/(tests\/e2e\/tests\/blocks\/.*)$/;

const median = ( values ) => {
	const sorted = [ ...values ].sort( ( a, b ) => a - b );
	const middle = Math.floor( sorted.length / 2 );

	return sorted.length % 2 === 0
		? Math.round( ( sorted[ middle - 1 ] + sorted[ middle ] ) / 2 )
		: sorted[ middle ];
};

const directories = process.argv.slice( 2 );

if ( directories.length === 0 ) {
	console.error(
		'Usage: node tests/e2e/bin/update-blocks-shard-durations.mjs <dir> [<dir> ...]'
	);
	process.exit( 1 );
}

// One total per file per run, so a file split across shards is summed before
// the median is taken across runs rather than after.
const perRun = new Map();

for ( const directory of directories ) {
	const reports = readdirSync( directory, {
		recursive: true,
		withFileTypes: true,
	} ).filter(
		( entry ) =>
			entry.isFile() &&
			entry.name.startsWith( 'ctrf-report-' ) &&
			entry.name.endsWith( '.json' )
	);

	if ( reports.length === 0 ) {
		console.warn( `No ctrf-report-*.json found under ${ directory }` );
		continue;
	}

	const totals = new Map();

	for ( const report of reports ) {
		const file = path.join( report.parentPath ?? report.path, report.name );
		const { results } = JSON.parse( readFileSync( file, 'utf8' ) );

		for ( const testCase of results.tests ?? [] ) {
			const match = SPEC_PATTERN.exec( testCase.filePath ?? '' );

			if ( ! match ) {
				continue;
			}

			totals.set(
				match[ 1 ],
				( totals.get( match[ 1 ] ) ?? 0 ) + ( testCase.duration ?? 0 )
			);
		}
	}

	perRun.set( directory, totals );
	console.log(
		`${ directory }: ${ reports.length } reports, ${ totals.size } spec files`
	);
}

const samples = new Map();

for ( const totals of perRun.values() ) {
	for ( const [ file, duration ] of totals ) {
		samples.set( file, [ ...( samples.get( file ) ?? [] ), duration ] );
	}
}

if ( samples.size === 0 ) {
	console.error( 'No Blocks spec durations found. Nothing written.' );
	process.exit( 1 );
}

const existing = JSON.parse( readFileSync( DURATIONS_FILE, 'utf8' ) );
const durations = Object.fromEntries(
	[ ...samples.entries() ]
		.map( ( [ file, values ] ) => [ file, median( values ) ] )
		.sort( ( [ a ], [ b ] ) => ( a < b ? -1 : a > b ? 1 : 0 ) )
);

writeFileSync(
	DURATIONS_FILE,
	`${ JSON.stringify(
		{
			...existing,
			generatedFrom: `${ perRun.size } run(s), ${
				new Date().toISOString().split( 'T' )[ 0 ]
			}`,
			durations,
		},
		null,
		1
	) }\n`
);

const total = Object.values( durations ).reduce( ( sum, ms ) => sum + ms, 0 );

console.log(
	`Wrote ${ Object.keys( durations ).length } spec files, ${ (
		total / 60000
	).toFixed( 1 ) } minutes total, to ${ path.relative(
		PLUGIN_ROOT,
		DURATIONS_FILE
	) }`
);
