const DURATION_MANIFEST_SCHEMA_VERSION = 1;

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

function optionalInventoryArray( value, label ) {
	if ( value === undefined || value === null ) {
		return [];
	}
	if ( ! Array.isArray( value ) ) {
		throw new Error(
			`Invalid Playwright inventory: ${ label } must be an array`
		);
	}
	return value;
}

function validateInventoryObject( value, label ) {
	if ( ! value || typeof value !== 'object' || Array.isArray( value ) ) {
		throw new Error(
			`Invalid Playwright inventory: ${ label } entries must be objects`
		);
	}
}

function collectProjectFiles( report, projectName ) {
	const files = new Set();

	function visitSuites( suites, label ) {
		for ( const suite of optionalInventoryArray( suites, label ) ) {
			validateInventoryObject( suite, 'suite' );
			for ( const spec of optionalInventoryArray(
				suite.specs,
				'suite.specs'
			) ) {
				validateInventoryObject( spec, 'spec' );
				const tests = optionalInventoryArray(
					spec.tests,
					'spec.tests'
				);
				for ( const playwrightTest of tests ) {
					validateInventoryObject( playwrightTest, 'test' );
				}
				if (
					tests.some(
						( playwrightTest ) =>
							playwrightTest.projectName === projectName
					)
				) {
					if (
						typeof spec.file !== 'string' ||
						spec.file.length === 0
					) {
						throw new Error(
							'Invalid Playwright inventory: Matching spec.file must be a non-empty string'
						);
					}
					files.add( spec.file.replaceAll( '\\', '/' ) );
				}
			}
			visitSuites( suite.suites, 'suite.suites' );
		}
	}

	visitSuites( report?.suites, 'suites' );
	return [ ...files ].sort( compareFilePaths );
}

function planDurationShards( { files, manifest, shardCount } ) {
	validateDurationManifest( manifest );
	const weightedFiles = files.map( ( file ) => ( {
		file,
		durationMs: manifest.files[ file ] ?? manifest.fallbackDurationMs,
	} ) );

	return assignDurationShards( weightedFiles, shardCount );
}

/**
 * Guard against a plan that would drop or duplicate a spec file.
 *
 * Playwright accepts a run in which every test was excluded, so a gap here
 * would report success while testing nothing.
 *
 * @param {Array<{files: string[]}>} shards Planned shards.
 * @param {Iterable<string>}         files  Discovered spec files.
 */
function assertPlanCoversCorpus( shards, files ) {
	const corpus = new Set( files );
	const planned = new Set();

	for ( const shard of shards ) {
		for ( const file of shard.files ) {
			if ( planned.has( file ) ) {
				throw new Error(
					`Blocks shard plan assigns ${ file } to more than one shard`
				);
			}
			planned.add( file );
		}
	}

	const missing = [ ...corpus ].filter( ( file ) => ! planned.has( file ) );
	const unexpected = [ ...planned ].filter(
		( file ) => ! corpus.has( file )
	);

	if ( missing.length > 0 || unexpected.length > 0 ) {
		throw new Error(
			`Blocks shard plan does not match the discovered suite. Missing: ${
				missing.join( ', ' ) || 'none'
			}. Unexpected: ${ unexpected.join( ', ' ) || 'none' }.`
		);
	}
}

/**
 * Resolve the spec files that belong to one shard.
 *
 * An unusable manifest is a balancing problem rather than a coverage one, so it
 * is reported back as a fallback reason and the caller can leave Playwright's
 * own sharding in place. A plan that does not cover the discovered suite is a
 * coverage problem and always throws.
 *
 * @param {Object}                           options
 * @param {string[]}                         options.files    Discovered spec files.
 * @param {Object}                           options.manifest Duration manifest.
 * @param {{current: number, total: number}} options.shard    Requested shard.
 * @return {{files: Set<string>|null, fallbackReason: string|null}} Selection.
 */
function selectShardFiles( { files, manifest, shard } ) {
	let shards;

	try {
		shards = planDurationShards( {
			files,
			manifest,
			shardCount: shard.total,
		} );
	} catch ( error ) {
		return { files: null, fallbackReason: error.message };
	}

	assertPlanCoversCorpus( shards, files );

	return {
		files: new Set( shards[ shard.current - 1 ].files ),
		fallbackReason: null,
	};
}

/**
 * Compare a duration manifest against freshly measured durations.
 *
 * Reports how far the committed plan has drifted from reality, at the
 * granularity that matters: per shard, since the slowest shard is the CI
 * critical path. Individual spec timings are far noisier than shard totals, so
 * `drifts` is for naming culprits, not for deciding whether to act.
 *
 * @param {Object}                options
 * @param {Object}                options.manifest        Committed duration manifest.
 * @param {Record<string,number>} options.actualDurations Measured ms per spec file.
 * @param {number}                options.shardCount      Shards the plan is built for.
 * @return {Object} Drift summary.
 */
function summarizeManifestDrift( { manifest, actualDurations, shardCount } ) {
	validateDurationManifest( manifest );

	const files = Object.keys( actualDurations ).sort( compareFilePaths );
	if ( files.length === 0 ) {
		throw new Error( 'No measured durations were supplied' );
	}

	const shards = planDurationShards( {
		files,
		manifest,
		shardCount,
	} ).map( ( shard ) => {
		const actualMs = shard.files.reduce(
			( total, file ) => total + actualDurations[ file ],
			0
		);
		return {
			index: shard.index,
			modelledMs: shard.durationMs,
			actualMs,
			deviation: ( actualMs - shard.durationMs ) / shard.durationMs,
		};
	} );

	const modelledTotalMs = shards.reduce(
		( total, shard ) => total + shard.modelledMs,
		0
	);
	const actualTotalMs = shards.reduce(
		( total, shard ) => total + shard.actualMs,
		0
	);

	const drifts = files
		.filter( ( file ) => manifest.files[ file ] !== undefined )
		.map( ( file ) => ( {
			file,
			modelledMs: manifest.files[ file ],
			actualMs: actualDurations[ file ],
			deltaMs: actualDurations[ file ] - manifest.files[ file ],
		} ) )
		.filter( ( drift ) => drift.deltaMs !== 0 )
		.sort(
			( first, second ) =>
				Math.abs( second.deltaMs ) - Math.abs( first.deltaMs ) ||
				compareFilePaths( first.file, second.file )
		);

	return {
		shardCount,
		modelledTotalMs,
		actualTotalMs,
		totalDeviation: ( actualTotalMs - modelledTotalMs ) / modelledTotalMs,
		worstShardDeviation: Math.max(
			...shards.map( ( shard ) => Math.abs( shard.deviation ) )
		),
		shards,
		newFiles: files.filter(
			( file ) => manifest.files[ file ] === undefined
		),
		staleFiles: Object.keys( manifest.files )
			.filter( ( file ) => actualDurations[ file ] === undefined )
			.sort( compareFilePaths ),
		drifts,
	};
}

module.exports = {
	assertPlanCoversCorpus,
	assignDurationShards,
	collectProjectFiles,
	planDurationShards,
	selectShardFiles,
	summarizeManifestDrift,
	validateDurationManifest,
};
