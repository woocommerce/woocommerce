/**
 * External dependencies
 */
import matter from 'gray-matter';
import yaml from 'js-yaml';

/**
 * Generate front-matter for supported post attributes.
 *
 * @param fileContents
 */
export const generatePostFrontMatter = (
	fileContents: string,
	includeContent?: boolean
): {
	[ key: string ]: unknown;
} => {
	const allowList = [
		'post_date',
		'post_title',
		'page_template',
		'post_author',
		'post_name',
		'category_title',
		'category_slug',
		'content',
		'menu_title',
		'tags',
	];

	const frontMatter = matter( fileContents, {
		engines: {
			// By passing yaml.JSON_SCHEMA we disable date parsing that changes date format.
			// See https://github.com/jonschlinkert/gray-matter/issues/62#issuecomment-577628177 for more details.
			yaml: ( s ) => yaml.load( s, { schema: yaml.JSON_SCHEMA } ),
		},
	} );
	const content = frontMatter.content.split( '\n' );
	const headings = content.filter(
		( line ) => line.substring( 0, 2 ) === '# '
	);
	const title = headings[ 0 ]?.substring( 2 ).trim();

	frontMatter.data.post_title = frontMatter.data.post_title ?? title;
	if ( includeContent ?? false ) {
		frontMatter.data.content = frontMatter.content;
	}
	return Object.keys( frontMatter.data )
		.filter( ( key ) => allowList.includes( key ) )
		.reduce( ( obj, key ) => {
			obj[ key ] = frontMatter.data[ key ];
			return obj;
		}, {} );
};
