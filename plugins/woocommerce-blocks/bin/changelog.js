#!/usr/bin/env node
'use strict';
/* eslint-disable */

const requestPromise = require( 'request-promise' );
const chalk = require( 'chalk' );
const octokit = require( '@octokit/rest' )();
const promptly = require( 'promptly' );

const REPO = 'woocommerce/woocommerce-gutenberg-products-block';

const headers = {
	'Content-Type': 'application/json;charset=UTF-8',
	'Authorization': `token ${process.env.GH_API_TOKEN}`,
	'Accept': 'application/vnd.github.inertia-preview+json',
	'User-Agent': 'request'
};

const getPullRequestType = labels => {
	const typeLabel = labels.find( label => label.name.includes( 'type:' ) );
	if ( ! typeLabel ) {
		return 'dev';
	}
	return typeLabel.name.replace( 'type: ', '' );
};

const isCollaborator = async ( username ) => {
	return requestPromise( {
		url: `https://api.github.com/orgs/woocommerce/members/${ username }`,
		headers,
		resolveWithFullResponse: true
	} ).then( response => {
		return response.statusCode === 204;
	} )
		.catch( err => {
			if ( err.statusCode !== 404 ) {
				console.log( '🤯' );
				console.log( err.message );
			}
		});
}

const isMergedPullRequest = async ( pullRequestUrl ) => {
	const options = {
		url: pullRequestUrl,
		headers,
		json: true,
	};
	return requestPromise( options )
		.then( data => data.merged )
		.catch( err => {
			console.log( '🤯' );
			console.log( err.message );
		});
}

const getEntry = async ( data ) => {
	if ( ! data.pull_request ) {
		return;
	}

	const isMerged = await isMergedPullRequest( data.pull_request.url );
	const skipChangelog = data.labels.find( label => label.name === 'skip-changelog' );

	if ( ! isMerged || skipChangelog ) {
		return;
	}

	const collaborator = await isCollaborator( data.user.login );
	const type = getPullRequestType( data.labels );
	const authorTag = collaborator ? '' : `👏 @${ data.user.login }`;
	let title;
	if ( /### Changelog\r\n\r\n> /.test( data.body ) ) {
		const bodyParts = data.body.split( '### Changelog\r\n\r\n> ' );
		const note = bodyParts[ bodyParts.length - 1 ];
		title = note
			// Remove comment prompt
			.replace( /<!---(.*)--->/gm, '' )
			// Remove new lines and whitespace
			.trim();
		if ( ! title.length ) {
			title = `${ type }: ${ data.title }`;
		}
	} else {
		title = `${ type }: ${ data.title }`;
	}
	return `- ${ title } #${ data.number } ${ authorTag }`;
};

const makeChangelog = async version => {
	const results = await octokit.search.issuesAndPullRequests( {
		q: `milestone:${ version }+type:pr+repo:${ REPO }`,
		sort: 'reactions',
		per_page: 100,
	} );
	const entries = await Promise.all( results.data.items.map( async pr => await getEntry( pr ) ) );

	if ( ! entries || ! entries.length ) {
		console.log( chalk.yellow( "This version doesn't have any associated PR." ) );
		return;
	}

	const filteredEntries = entries.filter( Boolean );

	if ( ! entries || ! entries.length ) {
		console.log( chalk.yellow( "None of the PRs of this version are eligible for the changelog." ) );
		return;
	}

	console.log( filteredEntries.join( '\n' ) );
};

( async () => {
	console.log( chalk.yellow( 'This program requires an api token. You can create one here: ' ) + 'https://github.com/settings/tokens' );
	console.log( '' );
	console.log( chalk.yellow( 'Token scope will require read permissions on public_repo, admin:org, and user.' ) );
	console.log( '' );
	console.log( chalk.yellow( 'Export the token as variable called GH_API_TOKEN from your bash profile.' ) );
	console.log( '' );

	const ready = await promptly.confirm( 'Are you ready to continue? ' );

	if ( ready ) {
		console.log( '' );
		console.log( chalk.yellow( 'In order to generate the changelog, you will have to provide a version number to retrieve the PRs from.' ) );
		console.log( '' );
		console.log( chalk.yellow( 'Write it as it appears in the milestones page: ' ) + 'https://github.com/woocommerce/woocommerce-gutenberg-products-block/milestones' );
		console.log( '' );
		const version = await promptly.prompt( 'Version number: ' );
		console.log( '' );
		console.log( chalk.green( 'Here is the generated changelog. Be sure to remove entries ' +
			'not intended for a WooCommerce Blocks release.' ) );
		console.log( '' );
		makeChangelog( version );
	} else {
		console.log( '' );
		console.log( chalk.yellow( 'Ok, see you soon.' ) );
		console.log( '' );
	}
} )();
