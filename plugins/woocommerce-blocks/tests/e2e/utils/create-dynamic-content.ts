/**
 * External dependencies
 */
import { readFile } from 'fs/promises';
import Handlebars from 'handlebars';
import { exec } from 'child_process';

/**
 * Internal dependencies
 */
import { cli } from './cli';

export type GeneratedPost = {
	url: string;
	id: string;
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

const deletePost = async ( id: string ) => {
	await cli(
		`npm run wp-env run tests-cli -- wp post delete ${ id } --force`
	);
};

// Creates a post as user 1.
const createPost = async (
	title: string,
	templateContent: string
): Promise< GeneratedPost > => {
	return new Promise( ( resolve, reject ) => {
		const command = `npm run wp-env run tests-cli -- wp post create --porcelain --post_status=publish --post_author=1 --post_title="${ title }" -`;

		const childProcess = exec( command, ( error, stdout ) => {
			// stderr is not empty when the command succeeds, so the best we can do is check if stdout contains the id.
			// stdout also contains cruft from passing the command to wp-env, so we have to extract the ID from the output.
			const outputLines = stdout.trim().split( '\n' );
			const postId = outputLines.find( ( line ) =>
				line.match( /^\d+$/ )
			);

			if ( ! postId ) {
				reject( new Error( `Could not create post: ${ stdout }` ) );
			} else {
				resolve( {
					id: postId,
					url: `/${ title.toLowerCase().replace( /\s+/g, '-' ) }/`,
					deletePost: () => deletePost( postId ),
				} );
			}
		} );

		if ( childProcess.stdin ) {
			childProcess.stdin.write( templateContent );
			childProcess.stdin.end();
		} else {
			reject( new Error( 'Could not create post: stdin not available' ) );
		}
	} );
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
