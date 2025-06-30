/**
 * External dependencies
 */
const bold = require( 'chalk' );
const fs = require( 'fs' );
const path = require( 'path' );

/**
 * Internal dependencies
 */
const {
	getFilesFromDir,
	readJSONFile,
	logAtIndent,
	sanitizeBranchName,
	median
} = require( './utils' ) ;

const formats = {
	success: bold.green,
};

const ARTIFACTS_PATH =
	process.env.WP_ARTIFACTS_PATH || path.join( process.cwd(), 'artifacts' );
const RESULTS_FILE_SUFFIX = '.performance-results.json';

/**
 * Calculates and prints results from the generated reports.
 *
 * @param {string[]} testSuites Test suites we are aiming.
 * @param {string[]} branches   Branches we are aiming.
 */
async function processPerformanceReports(
	testSuites: string[],
	branches: string[]
): Promise< void > {
	logAtIndent( 0, 'Calculating results' );

	const resultFiles = getFilesFromDir( ARTIFACTS_PATH ).filter(
		( file: string ) => file.endsWith( RESULTS_FILE_SUFFIX )
	);
	const results: Record<
		string,
		Record< string, Record< string, number > >
	> = {};

	// Calculate medians from all rounds.
	for ( const testSuite of testSuites ) {
		logAtIndent( 1, 'Test suite:', formats.success( testSuite ) );

		results[ testSuite ] = {};
		for ( const branch of branches ) {
			const sanitizedBranchName = sanitizeBranchName( branch );
			const resultsRounds: any[] = resultFiles
				.filter( ( file: string ) =>
					file.includes(
						`/${ testSuite }_${ sanitizedBranchName }_round-`
					)
				)
				.map( ( file: string ) => {
					logAtIndent( 2, 'Reading from:', formats.success( file ) );
					return readJSONFile( file );
				} );

			const metrics = Object.keys( resultsRounds[ 0 ] );
			results[ testSuite ][ branch ] = {};

			for ( const metric of metrics ) {
				const values = resultsRounds
					.map( ( round ) => round[ metric ] )
					.filter( ( value ) => typeof value === 'number' );

				const value = median( values );
				if ( value !== undefined ) {
					results[ testSuite ][ branch ][ metric ] = value;
				}
			}
		}
		const calculatedResultsPath = path.join(
			ARTIFACTS_PATH,
			testSuite + RESULTS_FILE_SUFFIX
		);

		logAtIndent(
			2,
			'Saving curated results to:',
			formats.success( calculatedResultsPath )
		);
		fs.writeFileSync(
			calculatedResultsPath,
			JSON.stringify( results[ testSuite ], null, 2 )
		);
	}

	logAtIndent( 0, 'Printing results' );

	for ( const testSuite of testSuites ) {
		logAtIndent( 0, formats.success( testSuite ) );

		// Invert the results, so we can display them in a table.
		const invertedResult: Record< string, Record< string, string > > = {};
		for ( const [ branch, metrics ] of Object.entries(
			results[ testSuite ]
		) ) {
			for ( const [ metric, value ] of Object.entries( metrics ) ) {
				invertedResult[ metric ] = invertedResult[ metric ] || {};
				invertedResult[ metric ][ branch ] = `${ value } ms`;
			}
		}

		// Print the results.
		// eslint-disable-next-line no-console
		console.table( invertedResult );
	}
}

module.exports = {
	processPerformanceReports,
};
