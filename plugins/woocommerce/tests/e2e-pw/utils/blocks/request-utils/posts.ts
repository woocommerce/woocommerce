/**
 * External dependencies
 */
import path from 'path';
import { readFile } from 'fs/promises';
import Handlebars from 'handlebars';
import { Post } from '@wordpress/e2e-test-utils-playwright/build-types/request-utils/posts';

/**
 * Internal dependencies
 */
import type { RequestUtils } from './index';

export interface PostCompiler {
	compile: ( data?: unknown ) => Promise< Post >;
}

/**
 * Creates a post from a Handlebars template file located in the
 * tests/e2e/content-templates directory.
 */
export async function createPostFromFile( this: RequestUtils, name: string ) {
	const filePrefix = 'post';
	const filePath = path.resolve(
		__dirname,
		'../../content-templates',
		`${ filePrefix }_${ name }.handlebars` // e.g. post_with-custom-filters.handlebars
	);

	const fileContent = await readFile( filePath, 'utf8' );

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

	const compiledTemplate = Handlebars.compile( fileContent );

	return <PostCompiler>{
		compile: async ( data: unknown ) => {
			const content = compiledTemplate( data );

			// We're calling the posts endpoint directly until we can use the
			// createPost method.
			// See https://github.com/WordPress/gutenberg/pull/59463 for more
			// details.
			return await this.rest( {
				method: 'POST',
				path: `/wp/v2/posts`,
				data: {
					status: 'publish',
					date_gmt: new Date().toISOString(),
					content,
					title: name,
				},
			} );
		},
	};
}
