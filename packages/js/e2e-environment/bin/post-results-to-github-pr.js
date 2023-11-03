const fs = require( 'fs' );
const path = require( 'path' );

const resultsFile = path.resolve( __dirname, '../test-results.json' );

const buildOutput = ( results ) => {
	const { TITLE, SMOKE_TEST_URL } = process.env;
	const resultKeys = Object.keys( results );

	let output = `## ${ TITLE }:\n\n`;
	output += `**Test URL:** ${ SMOKE_TEST_URL }\n`;

	resultKeys.forEach( ( key ) => {
		// The keys that we care about all start with 'num'
		if ( key.includes( 'num' ) ) {
			// match only capitalized words
			const words = key.match( /[A-Z][a-z]+/g );

			output += `**Total Number of ${ words.join( ' ' ) }:** ${
				results[ key ]
			}\n`;
		}
	} );

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
		output += 'The path to the `test-results.json` file may need to be updated.';
	}

	await github.rest.issues.createComment( {
		issue_number: context.issue.number,
		owner: context.repo.owner,
		repo: context.repo.repo,
		body: output,
	} );
};
