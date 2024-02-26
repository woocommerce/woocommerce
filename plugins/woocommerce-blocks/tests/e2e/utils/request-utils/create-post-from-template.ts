/**
 * External dependencies
 */
import Handlebars from 'handlebars';
import { readFile } from 'fs/promises';
import { CreatePostPayload } from '@wordpress/e2e-test-utils-playwright/build-types/request-utils/posts';

/**
 * Internal dependencies
 */
import type { RequestUtils } from './index';

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

export type PostPayload = Partial< CreatePostPayload >;

export async function createPostFromTemplate(
	this: RequestUtils,
	post: PostPayload,
	templatePath: string,
	data: unknown
) {
	const templateContent = await readFile( templatePath, 'utf8' );
	const content = Handlebars.compile( templateContent )( data );

	const payload: CreatePostPayload = {
		status: 'publish',
		date_gmt: new Date().toISOString(),
		content,
		...post,
	};

	return await this.createPost( payload );
}
