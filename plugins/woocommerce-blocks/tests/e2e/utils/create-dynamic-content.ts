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

export type TestingTemplate = {
	templateSlug: string;
	clearCustomisations: () => Promise< void >;
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

export const deletePost = async ( requestUtils: RequestUtils, id: number ) => {
	return requestUtils.rest( {
		method: 'DELETE',
		path: `/wp/v2/posts/${ id }`,
		params: {
			force: true,
		},
	} );
};

const createPost = async (
	requestUtils: RequestUtils,
	payload: CreatePostPayload
) => {
	// The underlying createPost method passes the payload as URI params, triggering URI too long errors
	// if you pass long blog post content.
	const post = await requestUtils.rest( {
		method: 'POST',
		path: `/wp/v2/posts`,
		data: { ...payload },
	} );
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

export type TemplatePayload = {
	id: string;
};

export const updateTemplatesContent = async (
	requestUtils: RequestUtils,
	template: TemplatePayload,
	templatePath: string,
	data: unknown
) => {
	const templateContent = await readFile( templatePath, 'utf8' );
	const content = Handlebars.compile( templateContent )( data );

	const payload = {
		content,
	};

	return requestUtils.rest( {
		method: 'POST',
		path: `/wp/v2/templates/${ template.id }`,
		data: { ...payload },
	} );
};
