const fs = require( 'fs' );
const path = require( 'path' );

const resultsFile = path.resolve( __dirname, '../test-results.json' );

const buildOutput = ( results ) => {
	const { TITLE } = process.env;

	let output = `${ TITLE }:\n`;
	output += `Total Number of Passed Tests: ${ results.numTotalTests }\n`;
	output += `Total Number of Failed Tests: ${ results.numFailedTests }\n`;
	output += `Total Number of Test Suites: ${ results.numTotalTestSuites }\n`;
	output += `Total Number of Passed Test Suites: ${ results.numPassedTestSuites }\n`;
	output += `Total Number of Failed Test Suites: ${ results.numFailedTestSuites }\n`;
}

module.exports = async ( { github, context } ) => {
	let output = '';

	if ( fs.existsSync( resultsFile ) ) {
		const data = fs.readFileSync( resultsFile );
		const results = JSON.parse( data );

		ouput = buildOutput( results );
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
