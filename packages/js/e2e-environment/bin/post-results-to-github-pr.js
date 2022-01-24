const fs = require( 'fs' );
const path = require( 'path' );

const resultsFile = path.resolve( __dirname, '../../../../plugins/woocommerce/tests/e2e/test-results.json' );

const buildOutput = ( results ) => {
	const { TITLE, SMOKE_TEST_URL } = process.env;

	let output = `## ${ TITLE }:\n\n`;
	output += `**Test URL:** ${ SMOKE_TEST_URL }\n`;
	output += `**Total Number of Passed Tests:** ${ results.numTotalTests }\n`;
	output += `**Total Number of Failed Tests:** ${ results.numFailedTests }\n`;
	output += `**Total Number of Test Suites:** ${ results.numTotalTestSuites }\n`;
	output += `**Total Number of Passed Test Suites:** ${ results.numPassedTestSuites }\n`;
	output += `**Total Number of Failed Test Suites:** ${ results.numFailedTestSuites }\n`;

	return output;
};

module.exports = async ( { github, context } ) => {
	let output = '';

	if ( fs.existsSync( resultsFile ) ) {
		const data = fs.readFileSync( resultsFile );
		const results = JSON.parse( data );

		output = buildOutput( results );
	} else {
		output = `## Test Results Not Found! \n\n`;
		output += 'The path to the `test-results.json` file may need to be updated in the `post-results-to-github-pr.js` script';
	}

	await github.rest.issues.createComment( {
		issue_number: context.issue.number,
		owner: context.repo.owner,
		repo: context.repo.repo,
		body: output,
	} );
};
