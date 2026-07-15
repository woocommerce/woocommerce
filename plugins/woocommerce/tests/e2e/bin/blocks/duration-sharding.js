const { globSync } = require( 'glob' );

const DURATION_MANIFEST_SCHEMA_VERSION = 1;
const DURATION_SHARD_COUNT = 10;

function compareFilePaths( first, second ) {
	if ( first < second ) {
		return -1;
	}
	if ( first > second ) {
		return 1;
	}
	return 0;
}

function assignDurationShards( weightedFiles, shardCount ) {
	if ( ! Number.isInteger( shardCount ) || shardCount <= 0 ) {
		throw new Error( 'Shard count must be a positive integer' );
	}

	if ( ! Array.isArray( weightedFiles ) || weightedFiles.length === 0 ) {
		throw new Error( 'At least one weighted file is required' );
	}
	const seenFiles = new Set();
	for ( const { file, durationMs } of weightedFiles ) {
		if ( typeof file !== 'string' || file.length === 0 ) {
			throw new Error( 'Weighted file paths must be non-empty strings' );
		}
		if ( seenFiles.has( file ) ) {
			throw new Error( `Duplicate weighted file: ${ file }` );
		}
		if ( ! Number.isFinite( durationMs ) || durationMs <= 0 ) {
			throw new Error(
				`Duration must be a positive number for ${ file }`
			);
		}
		seenFiles.add( file );
	}
	if ( shardCount > weightedFiles.length ) {
		throw new Error(
			'Shard count cannot exceed the number of weighted files'
		);
	}

	const shards = Array.from( { length: shardCount }, ( _, index ) => ( {
		index: index + 1,
		durationMs: 0,
		files: [],
	} ) );
	const sortedFiles = [ ...weightedFiles ].sort(
		( first, second ) =>
			second.durationMs - first.durationMs ||
			compareFilePaths( first.file, second.file )
	);

	for ( const weightedFile of sortedFiles ) {
		const lightestShard = shards.reduce( ( lightest, candidate ) =>
			candidate.durationMs < lightest.durationMs ? candidate : lightest
		);
		lightestShard.files.push( weightedFile.file );
		lightestShard.durationMs += weightedFile.durationMs;
	}

	return shards;
}

function parseDurationShard(
	value,
	expectedShardCount = DURATION_SHARD_COUNT
) {
	const match = /^(\d+)\/(\d+)$/.exec( value );
	const index = Number( match?.[ 1 ] );
	const count = Number( match?.[ 2 ] );

	if (
		! match ||
		count !== expectedShardCount ||
		index < 1 ||
		index > count
	) {
		throw new Error(
			`Duration shard must use N/${ expectedShardCount } with N between 1 and ${ expectedShardCount }`
		);
	}

	return { index, count };
}

function validateDurationManifest( manifest ) {
	if ( manifest?.schemaVersion !== DURATION_MANIFEST_SCHEMA_VERSION ) {
		throw new Error(
			`Unsupported duration manifest schema: ${ manifest?.schemaVersion }`
		);
	}
	if (
		! Number.isFinite( manifest.fallbackDurationMs ) ||
		manifest.fallbackDurationMs <= 0
	) {
		throw new Error(
			'Manifest fallbackDurationMs must be a positive number'
		);
	}
	if (
		! manifest.files ||
		typeof manifest.files !== 'object' ||
		Array.isArray( manifest.files )
	) {
		throw new Error( 'Manifest files must be an object' );
	}

	for ( const [ file, durationMs ] of Object.entries( manifest.files ) ) {
		if ( ! Number.isFinite( durationMs ) || durationMs <= 0 ) {
			throw new Error(
				`Manifest duration must be positive for ${ file }`
			);
		}
	}
}

function discoverBlocksSpecs( testsRoot ) {
	return globSync( 'blocks/**/*.spec.ts', {
		cwd: testsRoot,
		nodir: true,
	} )
		.map( ( file ) => file.replaceAll( '\\', '/' ) )
		.sort();
}

function planDurationShards( { files, manifest, shardCount } ) {
	validateDurationManifest( manifest );
	const weightedFiles = files.map( ( file ) => ( {
		file,
		durationMs: manifest.files[ file ] ?? manifest.fallbackDurationMs,
	} ) );

	return assignDurationShards( weightedFiles, shardCount );
}

module.exports = {
	assignDurationShards,
	discoverBlocksSpecs,
	parseDurationShard,
	planDurationShards,
	validateDurationManifest,
};
