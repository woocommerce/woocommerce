const fs = require('fs');
const path = require( 'path' );
const program = require( 'commander' );

program
	.usage( '<file ...> [options]' )
	.option( '--e2e', 'Parse E2E Test Results' )
	.option( '--smoke', 'Parse Smoke Test Results' )
	.parse( process.argv );

const resultsFile = path.resolve( __dirname, '../test-results.json' );

if ( fs.existsSync( resultsFile ) ) {
	const data = fs.readFileSync(resultsFile);
	const results = JSON.parse( data );
	let title = 'Test Results';

	title = program.e2e ? 'E2E Test Results' : title;
	title = program.smoke ? 'Smoke Test Results' : title;

	const totalTests = results.numTotalTests;
	const totalTestSuites = results.numTotalTestSuites;
	const totalPassedTestSuites = results.numPassedTestSuites
	const totalFailedTestSuites = results.numFailedTestSuites
	const totalFailedTests = results.numFailedTests;
	const totalPassedTests = results.numPassedTests;

	// TODO: create funciton that makes markdown based on a object
	/**
	 * e.g {}
	 */
	console.log(`
		## ${title}:
		**Total Number of Tests:** ${totalTests}
		**Total Number of Passed Tests:** ${totalPassedTests}
		**Total Number of Failed Tests:** ${totalFailedTests}
		**Total Number of Test Suites:** ${totalTestSuites}
		**Total Number of Passed Test Suites:** ${totalPassedTestSuites}
		**Total Number of Failed Test Suites:** ${totalFailedTestSuites}`
	);
} else {
	console.log(`
		## Test Results Not Found!
	`);
}
