/**
 * External dependencies
 */
import { execFile } from 'node:child_process';
import { promisify } from 'node:util';

// "owner/repo", where a repository name may contain dots but cannot be only dots.
export const REPOSITORY_PATTERN = /^[a-zA-Z0-9\-_]+\/(?!\.+$)[a-zA-Z0-9\-_.]+$/;

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
 * @return {(query: string, variables?: Object) => Promise<Object>} Resolves with the response data and rejects on HTTP or GraphQL errors. Network and HTTP failures have `requestFailed: true`.
 */
export function createGraphQL( rawToken ) {
	const token = rawToken.trim();
	// Node's header validation error would print the token, so reject bad characters with a fixed message.
	if ( ! /^[\x21-\x7E]+$/.test( token ) ) {
		throw new Error(
			'The GitHub API token contains characters a token cannot have'
		);
	}

	// Failures of the request itself, as opposed to GraphQL errors about one query, will fail every later request too.
	const requestFailure = ( message ) =>
		Object.assign( new Error( message ), { requestFailed: true } );

	return async ( query, variables = {} ) => {
		let response;
		try {
			response = await fetch( 'https://api.github.com/graphql', {
				method: 'POST',
				headers: {
					authorization: `bearer ${ token }`,
					'content-type': 'application/json',
				},
				body: JSON.stringify( { query, variables } ),
			} );
		} catch ( error ) {
			throw requestFailure(
				`Could not reach GitHub: ${ error.message }`
			);
		}

		if ( response.status === 401 ) {
			throw requestFailure( 'The given token is invalid' );
		}
		if ( ! response.ok ) {
			// GitHub explains failures such as SAML enforcement or rate limits in the body's message.
			const { message } =
				( await response.json().catch( () => null ) ) ?? {};
			throw requestFailure(
				`GitHub API request failed with HTTP ${ response.status }${
					message ? `: ${ message }` : ''
				}`
			);
		}

		let body;
		try {
			body = await response.json();
		} catch ( error ) {
			throw requestFailure(
				`Could not read GitHub's response: ${ error.message }`
			);
		}

		const { data, errors } = body ?? {};
		if ( errors?.length ) {
			const message = errors
				.map( ( error ) => error.message )
				.join( '\n' );
			// GitHub reports GraphQL rate limits with HTTP 200 and a RATE_LIMITED error.
			throw errors.some( ( error ) => error.type === 'RATE_LIMITED' )
				? requestFailure( message )
				: new Error( message );
		}

		return data;
	};
}
