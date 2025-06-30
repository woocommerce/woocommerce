/**
 * External dependencies
 */
const bold = require( 'chalk' );
const fs = require( 'fs' );
const path = require( 'path' );

/**
 * Internal dependencies
 */
const { readJSONFile, logAtIndent } = require( './utils' );

const formats = {
	success: bold.green,
};

const ARTIFACTS_PATH =
	process.env.WP_ARTIFACTS_PATH || path.join( process.cwd(), 'artifacts' );
const PERFORMANCE_FILE_SUFFIX = '.performance-results.json';
const DELTAS_FILE_SUFFIX = '.delta-results.json';

/**
 * Calculates and prints the deltas for server response time for each test suite.
 *
 * @param {string[]} testSuites Test suites we are aiming.
 * @param {string[]} branches   Branches we are aiming.
 */
function calculateDelta( testSuites: string[], branches: string[] ): void {
	logAtIndent( 0, 'Checking delta' );

	// Calculate medians from all rounds.
	for ( const testSuite of testSuites ) {
		logAtIndent( 1, 'Test suite:', formats.success( testSuite ) );

		const performanceResultsPath = path.join(
			ARTIFACTS_PATH,
			testSuite + PERFORMANCE_FILE_SUFFIX
		);

		const deltaMetrics = {} as { [ key: string ]: number };
		const performanceResults = readJSONFile(
			performanceResultsPath,
			'utf8'
		);
		const currentBranch = branches[ 0 ];
		const baseBranch = branches[ 1 ];

		const percentage =
			( performanceResults[ currentBranch ].serverResponse /
				performanceResults[ baseBranch ].serverResponse -
				1 ) *
			100;

		deltaMetrics.serverResponse = percentage;

		logAtIndent(
			2,
			'Server response delta:',
			formats.success( percentage.toFixed( 2 ) + '%' )
		);

		const deltaResultsPath = path.join(
			ARTIFACTS_PATH,
			testSuite + DELTAS_FILE_SUFFIX
		);

		logAtIndent(
			2,
			'Saving curated results to:',
			formats.success( deltaResultsPath )
		);
		fs.writeFileSync(
			deltaResultsPath,
			JSON.stringify( deltaMetrics, null, 2 )
		);
	}
}

module.exports = {
	calculateDelta,
};
