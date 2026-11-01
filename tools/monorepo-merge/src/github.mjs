/**
 * External dependencies
 */
import { execFile } from 'node:child_process';
import { promisify } from 'node:util';

/**
 * Gets a GitHub API token from GH_TOKEN, GITHUB_TOKEN, or the GitHub CLI.
 *
 * @return {Promise<string>} The token.
 */
export async function getToken() {
	const token = process.env.GH_TOKEN || process.env.GITHUB_TOKEN;
	if ( token ) {
		return token;
	}

	try {
		const { stdout } = await promisify( execFile )( 'gh', [
			'auth',
			'token',
			'--hostname',
			'github.com',
		] );
		if ( stdout.trim() ) {
			return stdout.trim();
		}
	} catch {}

	throw new Error(
		'Set GH_TOKEN or sign in with "gh auth login" to supply a GitHub API token'
	);
}

/**
 * Creates a function that sends GraphQL requests to the GitHub API.
 *
 * @param {string} token The GitHub API token.
 * @return {(query: string, variables?: Object) => Promise<Object>} Resolves with the response data and rejects on HTTP or GraphQL errors.
 */
export function createGraphQL( token ) {
	return async ( query, variables = {} ) => {
		const response = await fetch( 'https://api.github.com/graphql', {
			method: 'POST',
			headers: {
				authorization: `bearer ${ token }`,
				'content-type': 'application/json',
			},
			body: JSON.stringify( { query, variables } ),
		} );

		if ( response.status === 401 ) {
			throw new Error( 'The given token is invalid' );
		}
		if ( ! response.ok ) {
			throw new Error(
				`GitHub API request failed with HTTP ${ response.status }`
			);
		}

		const { data, errors } = await response.json();
		if ( errors?.length ) {
			throw new Error(
				errors.map( ( error ) => error.message ).join( '\n' )
			);
		}

		return data;
	};
}
