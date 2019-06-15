#!/usr/bin/env node
'use strict';

const requestPromise = require('request-promise');
const chalk = require( 'chalk' );
const octokit = require( '@octokit/rest' )();
const promptly = require('promptly');

const headers = {
	'Content-Type': 'application/json;charset=UTF-8',
	'Authorization': `token ${process.env.GH_API_TOKEN}`,
	'Accept': 'application/vnd.github.inertia-preview+json',
	'User-Agent': 'request'
};

const columnIds = [];

const getPullRequestType = labels => {
	const typeLabel = labels.find( label => label.name.includes( '[Type]' ) );
	if ( ! typeLabel ) {
		return 'Dev';
	}
	return typeLabel.name.replace( '[Type] ', '' );
};

const getLabels = labels => {
	return labels
		.filter( label => ! /\[.*\]/.test( label.name ) )
		.map( label => label.name )
		.join( ', ' );

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
	const options =  {
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

const writeEntry = async ( content_url ) => {
	const options = {
		url: content_url,
		headers,
		json: true
	}
	return requestPromise( options )
		.then( async data => {
			if ( data.pull_request ) {
				const isMerged = await isMergedPullRequest( data.pull_request.url );
				if ( isMerged ) {
					const collaborator = await isCollaborator( data.user.login );
					const type = getPullRequestType( data.labels );
					const labels = getLabels( data.labels );
					const labelTag = labels.length ? `(${ labels })` : '';
					const authorTag = collaborator ? '' : `👏 @${ data.user.login }`;
					const entry = `- ${ type }: ${ data.title } #${ data.number } ${ labelTag } ${ authorTag }`;
					console.log( entry );
				}
			}
		} )
		.catch( err => {
			console.log( '🤯' );
			console.log( err.message );
		});
};

const makeChangelog = async column_id => {
	octokit.paginate(
		'GET /projects/columns/:column_id/cards',
		{ headers, column_id },
		response => response.data.forEach( async card => {
			await writeEntry( card.content_url );
		} )
	).catch( err => {
		console.log( '🤯' );
		console.log( err.message );
	})
};

const printProjectColumns = async () => {
	const options = {
		url: 'https://api.github.com/projects/1492664/columns',
		headers,
		json: true,
	}

	return requestPromise( options )
		.then( data => {
			console.log( ' ' );
			console.log( chalk.yellow( 'The project board contains the following columns:' ) );
			console.log( ' ' );
			console.log( '+---------+-----------------------------' );
			console.log( '| id      | sprint name ' );
			console.log( '+---------+-----------------------------' );
			data.forEach( column => {
				columnIds.push( column.id.toString() );
				console.log( '| ' + chalk.green( column.id ) +  ' | ' + column.name );
			} );
			console.log( '+---------+-----------------------------' );
			console.log( ' ' );
		} );
}

( async () => {
	console.log( chalk.yellow( 'This program requires an api token. You can create one here: ' ) + 'https://github.com/settings/tokens' );
	console.log( '' );
	console.log( chalk.yellow( 'Token scope will require read permissions on public_repo, admin:org, and user.' ) );
	console.log( '' );
	console.log( chalk.yellow( 'Export the token as variable called GH_API_TOKEN from your bash profile.' ) );
	console.log( '' );

	const ready = await promptly.confirm( 'Are you ready to continue? ' );

	if ( ready ) {
		await printProjectColumns();
		const id = await promptly.prompt( chalk.yellow( 'Enter a column id: ' ) );

		if ( columnIds.includes( id ) ) {
			console.log( '' );
			console.log( chalk.green( 'Here is the generated changelog. Be sure to remove entries ' +
				'not intended for a WooCommerce Admin release.' ) );
			console.log( '' );
			makeChangelog( id );
		} else {
			console.log( '' );
			console.log( chalk.red( 'Invalid column id' ) );
			console.log( '' );
		}
	} else {
		console.log( '' );
		console.log( chalk.yellow( 'Ok, see you soon.' ) );
		console.log( '' );
	}
} )();

