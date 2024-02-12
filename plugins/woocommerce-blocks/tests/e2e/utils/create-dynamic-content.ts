/**
 * External dependencies
 */
import { readFile } from 'fs/promises';
import Handlebars from 'handlebars';
import {
	CreatePostPayload,
	Post,
} from '@wordpress/e2e-test-utils-playwright/build-types/request-utils/posts';
import { RequestUtils } from '@wordpress/e2e-test-utils-playwright';

export type TestingPost = {
	post: Post;
	deletePost: () => Promise< void >;
};

Handlebars.registerPartial(
	'wp-block',
	`
<!-- wp:{{blockName}} {{{stringify attributes}}} -->
{{> @partial-block }}
<!-- /wp:{{blockName}} -->
`
);

Handlebars.registerHelper( 'stringify', function ( context ) {
	return JSON.stringify( context );
} );

const deletePost = async ( requestUtils: RequestUtils, id: number ) => {
	return requestUtils.rest( {
		method: 'DELETE',
		path: `/wp/v2/posts/${ id }`,
		params: {
			force: true,
		},
	} );
};

const posts: number[] = [];

// For now, we maintain a mix of some templates where pages/posts are created at environment
// setup, and these new ones created at test runtime. Until we can change when the original
// posts and pages are created (to happen at start of test suite or use this new system),
// we must delete runtime created posts separately. If we delete everything at
// beginning or end of test suite running then the old templates will be deleted
// and a full environment restart will be needed.
export const deleteAllTemplatePosts = async ( requestUtils: RequestUtils ) => {
	await Promise.all( posts.map( ( id ) => deletePost( requestUtils, id ) ) );
	posts.length = 0;
};

const createPost = async (
	requestUtils: RequestUtils,
	payload: CreatePostPayload
) => {
	const post = await requestUtils.createPost( payload );
	posts.push( post.id );
	return post;
};

export type PostPayload = Partial< CreatePostPayload >;

export const createPostFromTemplate = async (
	requestUtils: RequestUtils,
	post: PostPayload,
	templatePath: string,
	data: unknown
) => {
	const templateContent = await readFile( templatePath, 'utf8' );
	const content = Handlebars.compile( templateContent )( data );

	const payload: CreatePostPayload = {
		status: 'publish',
		date_gmt: new Date().toISOString(),
		content,
		...post,
	};

	return createPost( requestUtils, payload );
};
