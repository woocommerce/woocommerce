/**
 * External dependencies
 */
import { readFile } from 'fs/promises';
import Handlebars from 'handlebars';

/**
 * Internal dependencies
 */
import { cli } from './cli';

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

const deletePost = async ( id: string ) => {
	await cli( `wp post delete ${ id } --force` );
};

// Creates a post as user 1.
const createPost = async ( title: string, templateContent: string ) => {
	const { stdout } = await cli(
		`wp post create --post_status=publish --post_author=1 --post_title="${ title }" "${ templateContent }"`
	);

	const match = stdout.match( /\d+/ );
	if ( match ) {
		const postId = match[ 0 ];
		return {
			url: `?p=${ postId }`,
			id: postId,
			deletePost: () => deletePost( postId ),
		};
	}
};

export const createPostFromTemplate = async (
	title: string,
	templatePath: string,
	data: unknown
) => {
	const templateContent = await readFile( templatePath, 'utf8' );
	const content = Handlebars.compile( templateContent )( data );
	return createPost( title, content );
};
