'use strict';

const requestPromise = require( 'request-promise' );
const { graphql } = require( '@octokit/graphql' );
const { pkg, REPO } = require( '../config' );

/* eslint no-console: 0 */

const headers = {
	authorization: `token ${ pkg.changelog.githubToken }`,
	'user-agent': 'changelog-tool',
};

const authedGraphql = graphql.defaults( { headers } );

const getPullRequestType = ( labels ) => {
	const typeLabel = labels.find( ( label ) =>
		label.name.includes( pkg.changelog.labelPrefix )
	);
	if ( ! typeLabel ) {
		return pkg.changelog.defaultPrefix;
	}
	return typeLabel.name.replace( `${ pkg.changelog.labelPrefix } `, '' );
};

const devNoteSuffix = ( labels ) => {
	const noteLabel = labels.find( ( label ) =>
		label.name.includes( pkg.changelog.devNoteLabel )
	);
	return noteLabel ? ' [DN]' : '';
};

const isCollaborator = async ( username ) => {
	return requestPromise( {
		url: `https://api.github.com/orgs/${
			REPO.split( '/' )[ 0 ]
		}/members/${ username }`,
		headers,
		resolveWithFullResponse: true,
	} )
		.then( ( response ) => {
			return response.statusCode === 204;
		} )
		.catch( ( err ) => {
			if ( err.statusCode !== 404 ) {
				console.log( '🤯' );
				console.log( err.message );
			}
		} );
};

const getEntry = async ( pullRequest ) => {
	if (
		pullRequest.labels.nodes.some(
			( label ) => label.name === pkg.changelog.skipLabel
		)
	) {
		return;
	}

	const collaborator = await isCollaborator( pullRequest.author.login );
	const type = getPullRequestType( pullRequest.labels.nodes );
	const authorTag = collaborator ? '' : `👏 @${ pullRequest.author.login }`;
	const devNote = devNoteSuffix( pullRequest.labels.nodes );
	let title;
	if ( /### Changelog\r\n\r\n> /.test( pullRequest.body ) ) {
		const bodyParts = pullRequest.body.split( '### Changelog\r\n\r\n> ' );
		const note = bodyParts[ bodyParts.length - 1 ];
		title = note
			// Remove comment prompt
			.replace( /<!---(.*)--->/gm, '' )
			// Remove new lines and whitespace
			.trim();
		if ( ! title.length ) {
			title = `${ type }: ${ pullRequest.title }`;
		} else {
			title = `${ type }: ${ title }`;
		}
	} else {
		title = `${ type }: ${ pullRequest.title }`;
	}
	return `- ${ title } [#${ pullRequest.number }](${ pullRequest.url })${ devNote } ${ authorTag }`;
};

module.exports = {
	authedGraphql,
	getEntry,
};
