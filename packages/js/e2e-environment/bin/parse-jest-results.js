const fs = require( 'fs' );
const path = require( 'path' );

const resultsFile = path.resolve( __dirname, '../test-results.json' );

module.exports = async ( { github, context } ) => {
	let output = '';

	if ( fs.existsSync( resultsFile ) ) {
		const data = fs.readFileSync( resultsFile );
		const results = JSON.parse( data );

		const { TITLE } = process.env;

		const totalTests = results.numTotalTests;
		const totalTestSuites = results.numTotalTestSuites;
		const totalPassedTestSuites = results.numPassedTestSuites;
		const totalFailedTestSuites = results.numFailedTestSuites;
		const totalFailedTests = results.numFailedTests;
		const totalPassedTests = results.numPassedTests;

		// TODO: create funciton that makes markdown based on a object
		/**
		 * e.g {}
		 */
		output = `
			## ${ TITLE }:
			**Total Number of Tests:** ${ totalTests }
			**Total Number of Passed Tests:** ${ totalPassedTests }
			**Total Number of Failed Tests:** ${ totalFailedTests }
			**Total Number of Test Suites:** ${ totalTestSuites }
			**Total Number of Passed Test Suites:** ${ totalPassedTestSuites }
			**Total Number of Failed Test Suites:** ${ totalFailedTestSuites }`;
	} else {
		output = `## Test Results Not Found!`;
	}

	await github.rest.issues.createComment( {
		issue_number: context.issue.number,
		owner: context.repo.owner,
		repo: context.repo.repo,
		body: output,
	} );
};
