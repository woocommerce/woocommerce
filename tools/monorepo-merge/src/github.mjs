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
	const token = ( process.env.GH_TOKEN || process.env.GITHUB_TOKEN )?.trim();
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
 * @param {string} rawToken The GitHub API token; surrounding whitespace is ignored.
 * @return {(query: string, variables?: Object) => Promise<Object>} Resolves with the response data and rejects on HTTP or GraphQL errors.
 */
export function createGraphQL( rawToken ) {
	const token = rawToken.trim();
	// Node's header validation error would print the token, so reject bad characters with a fixed message.
	if ( ! /^[\x21-\x7E]+$/.test( token ) ) {
		throw new Error(
			'The GitHub API token contains characters a token cannot have'
		);
	}

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
			// GitHub explains failures such as SAML enforcement or rate limits in the body's message.
			const { message } = await response.json().catch( () => ( {} ) );
			throw new Error(
				`GitHub API request failed with HTTP ${ response.status }${
					message ? `: ${ message }` : ''
				}`
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
