const core = require( '@actions/core' );
const fs = require( 'fs' );

const diff = core.getInput( 'diff' );
const prNumber = core.getInput( 'prNumber' );
const repository = core.getInput( 'repository' );

const changelogFileNameRegEx = new RegExp( `.*(?=\/changelog\/).*` );

if ( ! changelogFileNameRegEx.test( diff ) ) {
	core.setFailed( `No changelog detected.` );
	process.exit( 1 );
}

const result = changelogFileNameRegEx.exec( diff );
const changelogFilePath = result[ 0 ];

fs.readFile( changelogFilePath, 'utf8', function ( err, data ) {
	if ( err ) {
		core.setFailed( err );
		process.exit( 1 );
	}

	// Construct a regular expression from the PR number and repository. Escaped characters need to
	// be re-escaped.
	const prNumberRegEx = new RegExp(
		`\[#${ prNumber }\]\(https:\/\/github\.com\/${ repository }\/pull\/31348\)`.replace(
			/[-\/\\^$*+?.()|[\]{}]/g,
			'\\$&'
		)
	);

	if ( prNumberRegEx.test( data ) ) {
		core.info(
			'Pull request number has already been appended to changelog entry.'
		);
		core.setOutput( 'changesMade', false );
		process.exit( 0 );
	}

	const prLink = `[#${ prNumber }](https://github.com/${ repository }/pull/${ prNumber })`;

	core.info( `Appending pull request link ${ prLink }.` );

	const text = data.trim() + ' ' + prLink.trim() + '\n';

	fs.writeFile( changelogFilePath, text, function ( err ) {
		if ( err ) {
			core.setFailed( err );
			process.exit( 1 );
		}

		core.info( 'Pull request link successfully appended to changelog.' );
		core.setOutput( 'changelogFilePath', changelogFilePath );
		core.setOutput( 'changesMade', true );
		process.exit( 0 );
	} );
} );
