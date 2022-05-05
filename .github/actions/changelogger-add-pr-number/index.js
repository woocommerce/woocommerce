const fsPromises = require( 'fs' ).promises;

// Define inputs.
const diff = process.env.INPUT_DIFF;
const prNumber = process.env.INPUT_PRNUMBER;
const repository = process.env.INPUT_REPOSITORY;

//  Gather all added changelog entries.
const changelogFileNameRegEx = new RegExp( `.*(?=\/changelog\/).*`, 'gm' );
const changelogFilePaths = [];
let result = changelogFileNameRegEx.exec( diff );

while ( result != null ) {
	changelogFilePaths.push( result[ 0 ] );
	result = changelogFileNameRegEx.exec( diff );
}

// Construct a regular expression from the PR number and repository. Escaped characters need to be re-escaped.
const prNumberRegEx = new RegExp(
	`\[#${ prNumber }\]\(https:\/\/github\.com\/${ repository }\/pull\/${ prNumber }\)`.replace(
		/[-\/\\^$*+?.()|[\]{}]/g,
		'\\$&'
	)
);

// Create a pull request link.
const prLink = `[#${ prNumber }](https://github.com/${ repository }/pull/${ prNumber })`;

/**
 * Test Changelog entry for PR number and append a PR number if it's not already present.
 *
 * @param {string} path Changelog path
 * @param {string} data Changelog entry
 * @returns null
 */
const possiblyAppendPRNumber = async ( path, data ) => {
	// Check to see if the pr number already exists.
	if ( prNumberRegEx.test( data ) ) {
		console.log(
			'Pull request number has already been appended to changelog entry.'
		);
		return;
	}

	// Append the PR link
	const nextChangelogEntry = data.trim() + ' ' + prLink.trim() + '\n';

	try {
		await fsPromises.writeFile( path, nextChangelogEntry );
		hasChanges = true;
		console.log( 'Successfully added PR number to ' + path );
	} catch ( err ) {
		console.log( err );
		process.exit( 1 );
	}
};

/**
 * Read Changelog files.
 *
 * @param {Array<string>} paths Changelog paths
 */
const readFiles = async ( paths ) => {
	for ( const path of paths ) {
		try {
			const data = await fsPromises.readFile( path, 'utf8' );
			await possiblyAppendPRNumber( path, data );
		} catch ( err ) {
			console.log( err );
			process.exit( 1 );
		}
	}
};

if ( changelogFilePaths.length === 0 ) {
	console.log( 'No changelog files found.' );
	process.exit( 0 );
}

readFiles( changelogFilePaths )
	.then( () => {
		console.log( 'All changelog entries successfully checked.' );
		process.exit( 0 );
	} )
	.catch( ( err ) => {
		console.log( err );
		process.exit( 1 );
	} );
