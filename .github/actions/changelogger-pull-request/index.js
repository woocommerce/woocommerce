const core = require( '@actions/core' );
const fs = require( 'fs' );

const branch = core.getInput( 'branch' ).replace( '/', '-' );
const diff = core.getInput( 'diff' );
const prNumber = core.getInput( 'prNumber' );
const repository = core.getInput( 'repository' );

const changelogFileNameRegEx = new RegExp( `.*(?=\/changelog\/${ branch }).*` );

if ( ! changelogFileNameRegEx.test( diff ) ) {
	console.log( 'no changelog detected' );
	process.exit( 1 );
}

const result = changelogFileNameRegEx.exec( diff );
const changelogFilePath = result[ 0 ];

fs.readFile( changelogFilePath, 'utf8', function ( err, data ) {
	if ( err ) {
		console.log( err );
		process.exit( 1 );
	}

	const prNumberRegEx = new RegExp(
		`\[#${ prNumber }\]\(https:\/\/github\.com\/${ repository }\/pull\/31348\)`.replace(
			/[-\/\\^$*+?.()|[\]{}]/g,
			'\\$&'
		)
	);
	console.log( prNumberRegEx );

	if ( prNumberRegEx.test( data ) ) {
		console.log( 'Already present, move on.' );
		core.setOutput( 'changesMade', false );
		process.exit( 0 );
	}

	console.log( 'Write to entry this PR number: ' + prNumber );

	const prLink = `[#${ prNumber }](https://github.com/${ repository }/pull/${ prNumber })`;

	console.log( 'PR url is: ' + prLink );

	const text = data.trim() + ' ' + prLink.trim() + '\n';

	fs.writeFile( changelogFilePath, text, function ( err ) {
		if ( err ) {
			console.log( err );
			process.exit( 1 );
		}

		console.log( 'It has been written' );
		core.setOutput( 'changelogFilePath', changelogFilePath );
		core.setOutput( 'changesMade', true );
		process.exit( 0 );
	} );
} );
