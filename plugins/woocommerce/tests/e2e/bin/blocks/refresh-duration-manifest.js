#!/usr/bin/env node
const { execFileSync } = require( 'node:child_process' );
const { mkdtempSync, readFileSync, rmSync } = require( 'node:fs' );
const { tmpdir } = require( 'node:os' );
const path = require( 'node:path' );

const { summarizeManifestDrift } = require( './duration-sharding' );
const {
	main: generateManifest,
	readRunDurations,
} = require( './generate-duration-manifest' );

const BLOCKS_ARTIFACT_NAME = 'blocks-e2e-report-attempt-1';
const DEFAULT_REPOSITORY = 'woocommerce/woocommerce';
const DEFAULT_RUNS = 3;
const DEFAULT_THRESHOLD = 0.2;
const DEFAULT_SHARD_COUNT = 10;
const MANIFEST_PATH = path.join( __dirname, 'block-test-durations.json' );
const REFRESH_COMMAND =
	'pnpm --filter=@woocommerce/plugin-woocommerce test:e2e:blocks:refresh-sharding-manifest';

/**
 * Runs a command and returns its stdout.
 *
 * @param {string}   command Executable to run.
 * @param {string[]} args    Arguments for the executable.
 * @return {string} Captured stdout.
 */
function runCommand( command, args ) {
	return execFileSync( command, args, {
		encoding: 'utf8',
		stdio: [ 'ignore', 'pipe', 'pipe' ],
		maxBuffer: 64 * 1024 * 1024,
	} );
}

function parsePositiveInteger( value, label ) {
	const parsed = Number( value );
	if ( ! Number.isSafeInteger( parsed ) || parsed < 1 ) {
		throw new Error( `${ label } must be a positive integer` );
	}
	return parsed;
}

function parseArguments( args ) {
	const options = {
		runs: DEFAULT_RUNS,
		check: false,
		threshold: DEFAULT_THRESHOLD,
		repository: DEFAULT_REPOSITORY,
		shardCount: DEFAULT_SHARD_COUNT,
	};

	for ( let index = 0; index < args.length; index++ ) {
		switch ( args[ index ] ) {
			case '--check':
				options.check = true;
				break;
			case '--runs':
				options.runs = parsePositiveInteger(
					args[ ++index ],
					'--runs'
				);
				break;
			case '--shards':
				options.shardCount = parsePositiveInteger(
					args[ ++index ],
					'--shards'
				);
				break;
			case '--threshold': {
				const percent = Number( args[ ++index ] );
				if ( ! Number.isFinite( percent ) || percent <= 0 ) {
					throw new Error(
						'--threshold must be a positive percentage, for example 20'
					);
				}
				options.threshold = percent / 100;
				break;
			}
			case '--repository':
				options.repository = args[ ++index ];
				if ( ! options.repository ) {
					throw new Error( '--repository requires owner/name' );
				}
				break;
			default:
				throw new Error( `Unknown argument: ${ args[ index ] }` );
		}
	}

	return options;
}

/**
 * Downloads the Blocks report artifact from the most recent successful trunk
 * runs.
 *
 * Artifacts expire after seven days, so a run that is otherwise fine may have
 * nothing left to download. Those runs are skipped rather than treated as an
 * error, and only an outright shortfall stops the command.
 *
 * @param {Object}   options
 * @param {Function} options.run              Command runner, injected for tests.
 * @param {number}   options.runs             How many runs to collect.
 * @param {string}   options.repository       Repository in owner/name form.
 * @param {string}   options.workingDirectory Directory to download into.
 * @return {Array<{id: string, path: string}>} Collected run directories.
 */
function collectRunDirectories( { run, runs, repository, workingDirectory } ) {
	const listed = run( 'gh', [
		'run',
		'list',
		'--repo',
		repository,
		'--workflow',
		'ci.yml',
		'--branch',
		'trunk',
		'--status',
		'success',
		'--limit',
		String( runs * 4 ),
		'--json',
		'databaseId',
	] );

	let candidates;
	try {
		candidates = JSON.parse( listed );
	} catch ( error ) {
		throw new Error( 'Unable to parse the run list returned by gh', {
			cause: error,
		} );
	}

	const collected = [];
	const skipped = [];

	for ( const candidate of candidates ) {
		if ( collected.length >= runs ) {
			break;
		}

		const id = String( candidate.databaseId );
		const destination = path.join( workingDirectory, id );

		try {
			run( 'gh', [
				'run',
				'download',
				id,
				'--repo',
				repository,
				'--name',
				BLOCKS_ARTIFACT_NAME,
				'--dir',
				destination,
			] );
		} catch {
			skipped.push( id );
			continue;
		}

		collected.push( { id, path: destination } );
	}

	if ( collected.length < runs ) {
		throw new Error(
			`Only ${ collected.length } of ${ runs } runs still have a "${ BLOCKS_ARTIFACT_NAME }" artifact. ` +
				`CI artifacts have a seven-day retention window, so runs older than that no longer carry one. ` +
				`Skipped: ${ skipped.join( ', ' ) || 'none' }.`
		);
	}

	return collected;
}

function formatMinutes( milliseconds ) {
	return `${ ( milliseconds / 60000 ).toFixed( 2 ) } min`;
}

function formatPercent( ratio ) {
	return `${ ratio >= 0 ? '+' : '' }${ ( ratio * 100 ).toFixed( 1 ) }%`;
}

/**
 * Renders a drift summary for a human reading CI output or a terminal.
 *
 * @param {Object} summary   Result of `summarizeManifestDrift`.
 * @param {number} threshold Worst-shard deviation that counts as drifted.
 * @return {string} The report.
 */
function formatDriftReport( summary, threshold ) {
	const drifted = summary.worstShardDeviation > threshold;
	const lines = [];

	lines.push(
		drifted
			? `Blocks shard durations have DRIFTED: worst shard is off by ${ formatPercent(
					summary.worstShardDeviation
			  ) }, above the ${ ( threshold * 100 ).toFixed( 0 ) }% threshold.`
			: `Blocks shard durations are within threshold: worst shard is off by ${ formatPercent(
					summary.worstShardDeviation
			  ) }, under ${ ( threshold * 100 ).toFixed( 0 ) }%.`
	);
	lines.push(
		`Manifest models ${ formatMinutes(
			summary.modelledTotalMs
		) } against a measured ${ formatMinutes(
			summary.actualTotalMs
		) } (${ formatPercent( summary.totalDeviation ) }).`
	);

	lines.push( '', 'Per shard (modelled -> measured):' );
	for ( const shard of summary.shards ) {
		lines.push(
			`  ${ String( shard.index ).padStart( 2 ) }  ${ formatMinutes(
				shard.modelledMs
			) } -> ${ formatMinutes( shard.actualMs ) }  ${ formatPercent(
				shard.deviation
			) }`
		);
	}

	if ( summary.drifts.length > 0 ) {
		lines.push( '', 'Biggest per-file drifts:' );
		for ( const drift of summary.drifts.slice( 0, 5 ) ) {
			lines.push(
				`  ${ ( drift.deltaMs / 1000 ).toFixed( 1 ).padStart( 8 ) }s  ${
					drift.file
				}`
			);
		}
	}

	if ( summary.newFiles.length > 0 ) {
		lines.push(
			'',
			`Not in the manifest, running on the fallback weight: ${ summary.newFiles.join(
				', '
			) }`
		);
	}

	if ( summary.staleFiles.length > 0 ) {
		lines.push(
			'',
			`In the manifest but no longer measured: ${ summary.staleFiles.join(
				', '
			) }`
		);
	}

	if ( drifted ) {
		lines.push( '', `Refresh with: ${ REFRESH_COMMAND }` );
	}

	return lines.join( '\n' );
}

/**
 * Averages each spec file's duration across the collected runs.
 *
 * A file only present in some runs is averaged over the runs that measured it,
 * so a spec added midway through the window is not diluted toward zero.
 *
 * `readRunDurations` returns a Map, not a plain object.
 *
 * @param {Array<{path: string}>} directories Downloaded run directories.
 * @param {Function}              readRun     Duration reader, injected for tests.
 * @return {Record<string, number>} Mean duration per spec file.
 */
function mergeMeasuredDurations( directories, readRun = readRunDurations ) {
	const totals = new Map();
	const counts = new Map();

	for ( const directory of directories ) {
		for ( const [ file, duration ] of readRun( directory.path ) ) {
			totals.set( file, ( totals.get( file ) ?? 0 ) + duration );
			counts.set( file, ( counts.get( file ) ?? 0 ) + 1 );
		}
	}

	return Object.fromEntries(
		[ ...totals ].map( ( [ file, total ] ) => [
			file,
			total / counts.get( file ),
		] )
	);
}

async function main( args = process.argv.slice( 2 ), run = runCommand ) {
	const options = parseArguments( args );
	const workingDirectory = mkdtempSync(
		path.join( tmpdir(), 'wc-blocks-durations-' )
	);

	try {
		const directories = collectRunDirectories( {
			run,
			runs: options.runs,
			repository: options.repository,
			workingDirectory,
		} );

		if ( ! options.check ) {
			await generateManifest(
				directories.flatMap( ( directory ) => [
					'--run',
					`${ directory.id }=${ directory.path }`,
				] )
			);
			console.log(
				`Regenerated ${ path.relative(
					process.cwd(),
					MANIFEST_PATH
				) } from runs ${ directories
					.map( ( directory ) => directory.id )
					.join( ', ' ) }.`
			);
			return 0;
		}

		const summary = summarizeManifestDrift( {
			manifest: JSON.parse( readFileSync( MANIFEST_PATH, 'utf8' ) ),
			actualDurations: mergeMeasuredDurations( directories ),
			shardCount: options.shardCount,
		} );

		console.log( formatDriftReport( summary, options.threshold ) );
		return summary.worstShardDeviation > options.threshold ? 1 : 0;
	} finally {
		rmSync( workingDirectory, { recursive: true, force: true } );
	}
}

if ( require.main === module ) {
	main()
		.then( ( status ) => {
			process.exitCode = status;
		} )
		.catch( ( error ) => {
			console.error( error.message );
			process.exitCode = 2;
		} );
}

module.exports = {
	BLOCKS_ARTIFACT_NAME,
	collectRunDirectories,
	formatDriftReport,
	main,
	mergeMeasuredDurations,
	parseArguments,
};
