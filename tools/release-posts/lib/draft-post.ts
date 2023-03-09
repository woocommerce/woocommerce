/**
 * External dependencies
 */
import fetch from 'node-fetch';
import { Logger } from 'cli-core/src/logger';

/**
 * Fetch a post from WordPress.com
 *
 * @param {string} siteId    - The site to fetch from.
 * @param {string} postId    - The id of the post to fetch.
 * @param {string} authToken - WordPress.com auth token.
 * @return {Promise}      - A promise that resolves to the JSON API response.
 */
export const fetchWpComPost = async (
	siteId: string,
	postId: string,
	authToken: string
) => {
	try {
		const post = await fetch(
			`https://public-api.wordpress.com/rest/v1.1/sites/${ siteId }/posts/${ postId }`,
			{
				headers: {
					Authorization: `Bearer ${ authToken }`,
					'Content-Type': 'application/json',
				},
			}
		);

		if ( post.status !== 200 ) {
			const text = await post.text();
			throw new Error( `Error creating draft post: ${ text }` );
		}

		return post.json();
	} catch ( e: unknown ) {
		if ( e instanceof Error ) {
			Logger.error( e.message );
		}
	}
};

export const searchForPostsByCategory = async (
	siteId: string,
	search: string,
	category: string,
	authToken: string
) => {
	try {
		const post = await fetch(
			`https://public-api.wordpress.com/rest/v1.1/sites/${ siteId }/posts?${ new URLSearchParams(
				{ search, category }
			) }`,
			{
				headers: {
					Authorization: `Bearer ${ authToken }`,
					'Content-Type': 'application/json',
				},
				method: 'GET',
			}
		);

		if ( post.status !== 200 ) {
			const text = await post.text();
			throw new Error( `Error creating draft post: ${ text }` );
		}

		return ( await post.json() ).posts;
	} catch ( e: unknown ) {
		if ( e instanceof Error ) {
			Logger.error( e.message );
		}
	}
};

/**
 * Edit a post on wordpress.com
 *
 * @param {string} siteId      - The site to post to.
 * @param {string} postId      - The post to edit.
 * @param {string} postContent - Post content.
 * @param {string} authToken   - WordPress.com auth token.
 * @return {Promise}           - A promise that resolves to the JSON API response.
 */
export const editWpComPostContent = async (
	siteId: string,
	postId: string,
	postContent: string,
	authToken: string
) => {
	try {
		const post = await fetch(
			`https://public-api.wordpress.com/rest/v1.2/sites/${ siteId }/posts/${ postId }`,
			{
				method: 'POST',
				headers: {
					Authorization: `Bearer ${ authToken }`,
					'Content-Type': 'application/json',
				},
				body: JSON.stringify( {
					content: postContent,
				} ),
			}
		);

		if ( post.status !== 200 ) {
			const text = await post.text();
			throw new Error( `Error creating draft post: ${ text }` );
		}

		return post.json();
	} catch ( e: unknown ) {
		if ( e instanceof Error ) {
			Logger.error( e.message );
		}
	}
};

/**
 * Create a draft of a post on wordpress.com
 *
 * @param {string} siteId      - The site to post to.
 * @param {string} postTitle   - Post title.
 * @param {string} postContent - Post content.
 * @param {string} authToken   - WordPress.com auth token.
 * @return {Promise}           - A promise that resolves to the JSON API response.
 */
export const createWpComDraftPost = async (
	siteId: string,
	postTitle: string,
	postContent: string,
	tags: string[],
	authToken: string
) => {
	try {
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
					tags,
				} ),
			}
		);

		if ( post.status !== 200 ) {
			const text = await post.text();
			throw new Error( `Error creating draft post: ${ text }` );
		}

		return post.json();
	} catch ( e: unknown ) {
		if ( e instanceof Error ) {
			Logger.error( e.message );
		}
	}
};
