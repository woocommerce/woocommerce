/**
 * External dependencies
 */
import fetch from 'node-fetch';

/**
 * Internal dependencies
 */
import { getWordpressComAuthToken } from './oauth-helper';
import { getEnvVar } from './environment';
import { Logger } from './logger';

/**
 * Create a draft of a post on wordpress.com
 *
 * @param {string} siteId      - The site to post to.
 * @param {string} postTitle   - Post title.
 * @param {string} postContent - Post content.
 * @return {Promise}           - A promise that resolves to the JSON API response.
 */
export const createWpComDraftPost = async (
	siteId: string,
	postTitle: string,
	postContent: string
) => {
	const clientId = getEnvVar( 'WPCOM_OAUTH_CLIENT_ID', true );
	const clientSecret = getEnvVar( 'WPCOM_OAUTH_CLIENT_SECRET', true );

	try {
		const authToken = await getWordpressComAuthToken(
			clientId,
			clientSecret,
			siteId,
			'posts'
		);

		const post = await fetch(
			`https://public-api.wordpress.com/rest/v1.2/sites/${ siteId }/posts/new`,
			{
				method: 'POST',
				headers: {
					Authorization: `Bearer ${ authToken }`,
					'Content-Type': 'application/json',
				},
				body: JSON.stringify( {
					title: postTitle,
					content: postContent,
					status: 'draft',
				} ),
			}
		);

		return post.json();
	} catch ( e: unknown ) {
		if ( e instanceof Error ) {
			Logger.error( e.message );
		}
	}
};
