const fs = require( 'fs' );
const { getOctokit, context } = require( '@actions/github' );
const { setFailed, getInput } = require( '@actions/core' );
const { parseXml, getFilesWithNewErrors } = require( './utils/xml' );
const { generateMarkdownMessage } = require( './utils/markdown' );
const { addRecord } = require( './utils/airtable' );
const { addComment } = require( './utils/github' );

const runner = async () => {
	const token = getInput( 'repo-token', { required: true } );
	const octokit = getOctokit( token );
	const payload = context.payload;
	const repo = payload.repository.name;
	const owner = payload.repository.owner.login;
	const fileName = getInput( 'checkstyle', {
		required: true,
	} );
	const trunkFileName = getInput( 'checkstyle-trunk', {
		required: true,
	} );

	const newCheckStyleFile = fs.readFileSync( fileName );
	const newCheckStyleFileParsed = parseXml( newCheckStyleFile );
	const currentCheckStyleFile = fs.readFileSync( trunkFileName );
	const currentCheckStyleFileContentParsed = parseXml(
		currentCheckStyleFile
	);

	const { header } = generateMarkdownMessage( newCheckStyleFileParsed );
	const filesWithNewErrors = getFilesWithNewErrors(
		newCheckStyleFileParsed,
		currentCheckStyleFileContentParsed
	);

	const message =
		header +
		'\n' +
		( filesWithNewErrors.length > 0
			? `⚠️ ⚠️ This PR introduces new TS errors on ${ filesWithNewErrors.length } files: \n` +
			  '<details> \n' +
			  filesWithNewErrors.join( '\n\n' ) +
			  '\n' +
			  '</details>'
			: '🎉 🎉 This PR does not introduce new TS errors.' );

	if ( process.env[ 'CURRENT_BRANCH' ] !== 'trunk' ) {
		await addComment( {
			octokit,
			owner,
			repo,
			message,
			payload,
		} );
	}

	if ( process.env[ 'CURRENT_BRANCH' ] === 'trunk' ) {
		try {
			await addRecord( currentCheckStyleFileContentParsed.totalErrors );
		} catch ( error ) {
			setFailed( error );
		}
	}
};

runner();
