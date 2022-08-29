/**
 * External dependencies
 */
import fetch from 'node-fetch';
import { getEnvVar } from './environment';

/**
 * Create a draft of a post on wordpress.com
 *
 * @param {string} siteId      - The ID of the site to post to.
 * @param {string} authToken   - An auth token for the site.
 * @param {string} postTitle   - Post title.
 * @param {string} postContent - Post content.
 * @return {Promise}           - A promise that resolves to the JSON API response.
 */
export const createWpComDraftPost = async (
	siteId: string,
	authToken: string,
	postTitle: string,
	postContent: string
) => {
	const post = await fetch(
		`https://public-api.wordpress.com/rest/v1.2/${ siteId }/posts/new`,
		{
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				Authorization: `Bearer ${ authToken }`,
			},
			body: JSON.stringify( {
				title: postTitle,
				content: postContent,
				status: 'draft',
			} ),
		}
	);

	return post.json();
};
