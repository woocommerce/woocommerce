/**
 * Deterministic, duration-aware sharding for the Blocks e2e suite.
 *
 * Playwright's own `--shard` splits by test count and keeps whole files
 * together, which leaves our ten shards running anywhere from 8 to 17 minutes
 * because a handful of spec files are far heavier than the rest. These helpers
 * pack files into shards by measured duration instead, longest first.
 *
 * Every shard process computes the same partition independently, so the
 * ordering has to be fully deterministic: files are sorted by descending
 * duration and ties are broken on path, never on filesystem order.
 */

// Used for a spec file that has no recorded duration yet, which is the case for
// any newly added file. Deliberately above the median so a new file lands in a
// lighter shard rather than on top of an already heavy one.
export const DEFAULT_DURATION_MS = 12000;

/**
 * Packs spec files into shards of roughly equal measured duration.
 *
 * @param {string[]}               files       Spec file paths, relative to the plugin root.
 * @param {Object<string, number>} durations   Measured duration in ms, keyed by the same paths.
 * @param {number}                 shardCount  Number of shards to produce.
 * @param {number}                 defaultMs   Duration assumed for a file with no measurement.
 * @return {Array<{files: string[], estimatedMs: number}>} One entry per shard, in shard order.
 */
export function planShards(
	files,
	durations,
	shardCount,
	defaultMs = DEFAULT_DURATION_MS
) {
	if ( ! Number.isInteger( shardCount ) || shardCount < 1 ) {
		throw new Error(
			`Shard count must be a positive integer, got ${ shardCount }`
		);
	}

	const weigh = ( file ) => durations[ file ] ?? defaultMs;

	// `localeCompare` is locale-sensitive, so compare directly to keep every
	// shard process in agreement regardless of the runner's environment.
	const ordered = [ ...files ].sort( ( a, b ) => {
		const byDuration = weigh( b ) - weigh( a );

		if ( byDuration !== 0 ) {
			return byDuration;
		}

		return a < b ? -1 : a > b ? 1 : 0;
	} );

	const shards = Array.from( { length: shardCount }, () => [] );
	const loads = new Array( shardCount ).fill( 0 );

	for ( const file of ordered ) {
		let target = 0;

		for ( let index = 1; index < shardCount; index++ ) {
			if ( loads[ index ] < loads[ target ] ) {
				target = index;
			}
		}

		shards[ target ].push( file );
		loads[ target ] += weigh( file );
	}

	return shards.map( ( shardFiles, index ) => ( {
		files: shardFiles.sort( ( a, b ) => ( a < b ? -1 : a > b ? 1 : 0 ) ),
		estimatedMs: loads[ index ],
	} ) );
}

/**
 * Parses a `--shard-plan=N/M` argument.
 *
 * @param {string} value The argument value, for example `3/10`.
 * @return {{index: number, total: number}} One-based shard index and total.
 */
export function parseShardPlan( value ) {
	const match = /^(\d+)\/(\d+)$/.exec( String( value ).trim() );

	if ( ! match ) {
		throw new Error( `Expected --shard-plan=N/M, got "${ value }"` );
	}

	const index = Number( match[ 1 ] );
	const total = Number( match[ 2 ] );

	if ( index < 1 || index > total ) {
		throw new Error(
			`Shard ${ index } is out of range for a ${ total }-shard plan`
		);
	}

	return { index, total };
}

/**
 * Escapes a path so Playwright treats it as a literal filter rather than a
 * regular expression. Playwright matches positional arguments as regexes
 * against the full file path, so an unescaped dot would match any character.
 *
 * @param {string} filePath The path to escape.
 * @return {string} The escaped path.
 */
export function escapeForPlaywrightFilter( filePath ) {
	return filePath.replace( /[.*+?^${}()|[\]\\]/g, '\\$&' );
}
