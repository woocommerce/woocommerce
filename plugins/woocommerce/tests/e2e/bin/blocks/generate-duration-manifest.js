#!/usr/bin/env node
const { readdirSync, readFileSync, writeFileSync } = require( 'node:fs' );
const path = require( 'node:path' );
const prettier = require( 'prettier' );

const { discoverBlocksFiles } = require( './discover-blocks-files' );

const DEFAULT_OUTPUT_PATH = path.join( __dirname, 'block-test-durations.json' );
const BLOCKS_TEST_PATH = '/tests/e2e/tests/blocks/';

function compareFilePaths( first, second ) {
	if ( first < second ) {
		return -1;
	}
	if ( first > second ) {
		return 1;
	}
	return 0;
}

function collectCtrfReportPaths( directory ) {
	const reportPaths = [];
	for ( const entry of readdirSync( directory, { withFileTypes: true } ) ) {
		const entryPath = path.join( directory, entry.name );
		if ( entry.isDirectory() ) {
			reportPaths.push( ...collectCtrfReportPaths( entryPath ) );
		} else if ( /^ctrf-report-.*\.json$/.test( entry.name ) ) {
			reportPaths.push( entryPath );
		}
	}
	return reportPaths.sort( compareFilePaths );
}

function normalizeBlocksFilePath( filePath ) {
	const normalizedPath = filePath.replaceAll( '\\', '/' );
	const markerIndex = normalizedPath.indexOf( BLOCKS_TEST_PATH );
	if ( markerIndex === -1 ) {
		return undefined;
	}
	return `blocks/${ normalizedPath.slice(
		markerIndex + BLOCKS_TEST_PATH.length
	) }`;
}

function readRunDurations( directory ) {
	const durations = new Map();
	for ( const reportPath of collectCtrfReportPaths( directory ) ) {
		const report = JSON.parse( readFileSync( reportPath, 'utf8' ) );
		const testResults = report?.results?.tests;
		if ( testResults !== undefined && ! Array.isArray( testResults ) ) {
			throw new Error(
				`Invalid CTRF report ${ reportPath }: results.tests must be an array`
			);
		}
		for ( const testResult of testResults ?? [] ) {
			if (
				! testResult ||
				typeof testResult !== 'object' ||
				Array.isArray( testResult )
			) {
				throw new Error(
					`Invalid CTRF report ${ reportPath }: results.tests entries must be objects`
				);
			}
			if (
				testResult.status !== 'passed' ||
				typeof testResult.filePath !== 'string' ||
				! Number.isFinite( testResult.duration ) ||
				testResult.duration <= 0
			) {
				continue;
			}

			const file = normalizeBlocksFilePath( testResult.filePath );
			if ( file ) {
				durations.set(
					file,
					( durations.get( file ) ?? 0 ) + testResult.duration
				);
			}
		}
	}

	if ( durations.size === 0 ) {
		throw new Error( `No passed Blocks timings found in ${ directory }` );
	}

	return new Map(
		[ ...durations.entries() ].sort( ( first, second ) =>
			compareFilePaths( first[ 0 ], second[ 0 ] )
		)
	);
}

function median( values ) {
	if ( values.length === 0 ) {
		throw new Error( 'Median requires at least one value' );
	}
	const sortedValues = [ ...values ].sort(
		( first, second ) => first - second
	);
	const midpoint = Math.floor( sortedValues.length / 2 );
	if ( sortedValues.length % 2 === 1 ) {
		return sortedValues[ midpoint ];
	}
	return ( sortedValues[ midpoint - 1 ] + sortedValues[ midpoint ] ) / 2;
}

function percentileNearestRank( values, percentile ) {
	if (
		values.length === 0 ||
		! Number.isFinite( percentile ) ||
		percentile <= 0 ||
		percentile > 1
	) {
		throw new Error(
			'Percentile requires values and a percentile greater than 0 and no greater than 1'
		);
	}
	const sortedValues = [ ...values ].sort(
		( first, second ) => first - second
	);
	return sortedValues[ Math.ceil( percentile * sortedValues.length ) - 1 ];
}

function validateSourceRuns( runs ) {
	if ( runs.length < 3 ) {
		throw new Error(
			'At least three distinct source runs with passed Blocks timings are required to calculate median durations'
		);
	}
	const seenRuns = new Set();
	for ( const run of runs ) {
		if ( seenRuns.has( run.id ) ) {
			throw new Error( `Duplicate source run: ${ run.id }` );
		}
		seenRuns.add( run.id );
	}
}

function buildDurationManifest( { currentFiles, runs } ) {
	validateSourceRuns( runs );
	const measuredDurations = new Map();
	for ( const file of currentFiles ) {
		const samples = runs
			.map( ( run ) => run.durations.get( file ) )
			.filter( ( duration ) => duration !== undefined );
		if ( samples.length > 0 ) {
			measuredDurations.set( file, median( samples ) );
		}
	}

	if ( measuredDurations.size === 0 ) {
		throw new Error( 'No current files have timing data' );
	}

	const fallbackDurationMs = percentileNearestRank(
		[ ...measuredDurations.values() ],
		0.75
	);
	const files = {};
	for ( const file of [ ...new Set( currentFiles ) ].sort(
		compareFilePaths
	) ) {
		files[ file ] = measuredDurations.get( file ) ?? fallbackDurationMs;
	}

	return {
		schemaVersion: 1,
		sourceRuns: runs
			.map( ( run ) => run.id )
			.sort( ( first, second ) => first - second ),
		fallbackDurationMs,
		files,
	};
}

function parseRunArgument( value ) {
	const separatorIndex = value.indexOf( '=' );
	const id = Number( value.slice( 0, separatorIndex ) );
	const reportPath = value.slice( separatorIndex + 1 );
	if (
		separatorIndex <= 0 ||
		! Number.isSafeInteger( id ) ||
		id <= 0 ||
		reportPath.length === 0
	) {
		throw new Error( 'Run must use ID=PATH with a positive numeric ID' );
	}
	return { id, path: reportPath };
}

function parseArguments( args ) {
	const runs = [];
	let outputPath = DEFAULT_OUTPUT_PATH;

	for ( let index = 0; index < args.length; index++ ) {
		const argument = args[ index ];
		if ( argument === '--run' ) {
			if ( index + 1 >= args.length ) {
				throw new Error( 'Expected a value after --run' );
			}
			runs.push( parseRunArgument( args[ ++index ] ) );
		} else if ( argument.startsWith( '--run=' ) ) {
			runs.push( parseRunArgument( argument.slice( '--run='.length ) ) );
		} else if ( argument === '--output' ) {
			if ( index + 1 >= args.length ) {
				throw new Error( 'Expected a value after --output' );
			}
			outputPath = args[ ++index ];
		} else if ( argument.startsWith( '--output=' ) ) {
			outputPath = argument.slice( '--output='.length );
		} else {
			throw new Error( `Unknown argument: ${ argument }` );
		}
	}

	if ( runs.length < 3 ) {
		throw new Error(
			'Expected at least three distinct successful source runs to calculate median durations. Pass each run as --run RUN_ID=CTRF_REPORT_DIRECTORY'
		);
	}
	validateSourceRuns( runs );
	return {
		runs: runs.sort( ( first, second ) => first.id - second.id ),
		outputPath,
	};
}

function discoverCurrentFiles() {
	const result = discoverBlocksFiles();
	if ( result.status !== 0 ) {
		throw new Error(
			`Playwright test discovery exited with status ${ result.status }`
		);
	}
	return result.files;
}

function serializeDurationManifest( manifest ) {
	return prettier.format( JSON.stringify( manifest ), {
		...prettier.resolveConfig.sync( __filename ),
		parser: 'json',
	} );
}

function main(
	args = process.argv.slice( 2 ),
	discoverFiles = discoverCurrentFiles
) {
	const { runs, outputPath } = parseArguments( args );
	const manifest = buildDurationManifest( {
		currentFiles: discoverFiles(),
		runs: runs.map( ( run ) => ( {
			id: run.id,
			durations: readRunDurations( run.path ),
		} ) ),
	} );
	writeFileSync( outputPath, serializeDurationManifest( manifest ) );
}

if ( require.main === module ) {
	try {
		main();
	} catch ( error ) {
		console.error( error.message );
		process.exitCode = 1;
	}
}

module.exports = {
	buildDurationManifest,
	main,
	median,
	parseArguments,
	percentileNearestRank,
	readRunDurations,
};
