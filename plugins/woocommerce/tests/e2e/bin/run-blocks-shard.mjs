/**
 * Runs the Blocks e2e suite, optionally restricted to one duration-balanced shard.
 *
 * Usage:
 *   node tests/e2e/bin/run-blocks-shard.mjs                     # every spec file
 *   node tests/e2e/bin/run-blocks-shard.mjs --shard-plan=3/10   # shard 3 of 10
 *
 * Any other arguments are forwarded to Playwright untouched.
 */

/**
 * External dependencies
 */
import { spawnSync } from 'node:child_process';
import { readdirSync, readFileSync } from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

/**
 * Internal dependencies
 */
import {
	DEFAULT_DURATION_MS,
	escapeForPlaywrightFilter,
	parseShardPlan,
	planShards,
} from './blocks-shard-plan.mjs';

const PLUGIN_ROOT = path.resolve(
	path.dirname( fileURLToPath( import.meta.url ) ),
	'../../..'
);
const SPEC_DIR = 'tests/e2e/tests/blocks';
const DURATIONS_FILE = 'tests/e2e/blocks-shard-durations.json';
const CONFIG = 'tests/e2e/playwright.config.ts';
const PROJECT = 'blocks-chromium';

/**
 * Lists every Blocks spec file, relative to the plugin root, in sorted order.
 *
 * @return {string[]} The spec file paths.
 */
function findSpecFiles() {
	const absolute = path.join( PLUGIN_ROOT, SPEC_DIR );

	return readdirSync( absolute, { recursive: true, withFileTypes: true } )
		.filter(
			( entry ) => entry.isFile() && entry.name.endsWith( '.spec.ts' )
		)
		.map( ( entry ) =>
			path
				.relative(
					PLUGIN_ROOT,
					path.join( entry.parentPath ?? entry.path, entry.name )
				)
				.split( path.sep )
				.join( '/' )
		)
		.sort();
}

/**
 * Reads the committed duration manifest.
 *
 * @return {{durations: Object<string, number>, defaultMs: number}} The manifest.
 */
function loadDurations() {
	try {
		const parsed = JSON.parse(
			readFileSync( path.join( PLUGIN_ROOT, DURATIONS_FILE ), 'utf8' )
		);

		return {
			durations: parsed.durations ?? {},
			defaultMs: parsed.defaultMs ?? DEFAULT_DURATION_MS,
		};
	} catch ( error ) {
		// A missing or unreadable manifest must not stop the suite running. The
		// plan degrades to an even split by file count, which is no worse than
		// what Playwright's own sharding does.
		console.warn(
			`::warning::Could not read ${ DURATIONS_FILE } (${ error.message }). Falling back to equal weights.`
		);

		return { durations: {}, defaultMs: DEFAULT_DURATION_MS };
	}
}

const passthrough = [];
let shardPlan = null;

for ( const arg of process.argv.slice( 2 ) ) {
	if ( arg.startsWith( '--shard-plan=' ) ) {
		shardPlan = parseShardPlan( arg.slice( '--shard-plan='.length ) );
	} else {
		passthrough.push( arg );
	}
}

const specFiles = findSpecFiles();

if ( specFiles.length === 0 ) {
	console.error( `No spec files found under ${ SPEC_DIR }` );
	process.exit( 1 );
}

const args = [
	'playwright',
	'test',
	`--config=${ CONFIG }`,
	`--project=${ PROJECT }`,
];

// On a re-run, ci.yml appends `--last-failed --shard=1/1` and this job has
// already downloaded its own `.last-run.json`. Those failures are by definition
// the ones this shard ran, so Playwright can select them on its own. Adding the
// file filters on top would only re-intersect the same set, and would drop a
// test outright if the duration manifest changed between the two runs.
const isLastFailedRun = passthrough.includes( '--last-failed' );

if ( shardPlan && ! isLastFailedRun ) {
	const { durations, defaultMs } = loadDurations();
	const shards = planShards(
		specFiles,
		durations,
		shardPlan.total,
		defaultMs
	);

	// Guard against a partition that would silently drop or duplicate a file.
	// Getting this wrong means tests stop running while CI stays green, which
	// is far worse than an unbalanced shard.
	const assigned = shards.flatMap( ( shard ) => shard.files );
	const unique = new Set( assigned );

	if (
		assigned.length !== specFiles.length ||
		unique.size !== specFiles.length
	) {
		console.error(
			`Shard plan covers ${ unique.size } of ${ specFiles.length } spec files. Refusing to run a partial suite.`
		);
		process.exit( 1 );
	}

	const unmeasured = specFiles.filter( ( file ) => ! ( file in durations ) );

	if ( unmeasured.length > 0 ) {
		console.warn(
			`::warning::${ unmeasured.length } spec file(s) have no recorded duration and were weighted at ${ defaultMs }ms. Refresh ${ DURATIONS_FILE }.`
		);
	}

	const shard = shards[ shardPlan.index - 1 ];

	console.log(
		`Blocks shard ${ shardPlan.index }/${ shardPlan.total }: ${
			shard.files.length
		} spec files, estimated ${ Math.round( shard.estimatedMs / 1000 ) }s`
	);

	args.push( ...shard.files.map( escapeForPlaywrightFilter ) );
} else if ( shardPlan ) {
	console.log(
		`Blocks shard ${ shardPlan.index }/${ shardPlan.total }: re-running previously failed tests, shard filters not applied.`
	);
}

args.push( ...passthrough );

const result = spawnSync( 'pnpm', args, {
	cwd: PLUGIN_ROOT,
	stdio: 'inherit',
} );

if ( result.error ) {
	console.error( result.error.message );
	process.exit( 1 );
}

process.exit( result.status ?? 1 );
