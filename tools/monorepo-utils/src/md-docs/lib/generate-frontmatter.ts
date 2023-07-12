/**
 * External dependencies
 */
import matter from 'gray-matter';

/**
 * Generate front-matter for supported post attributes.
 *
 * @param fileContents
 */
export const generatePostFrontMatter = (
	fileContents: string
): {
	[ key: string ]: unknown;
} => {
	const allowList = [
		'post_date',
		'post_title',
		'page_template',
		'post_author',
		'post_name',
	];

	const frontMatter = matter( fileContents ).data;

	console.log( 'frontm', frontMatter );

	return Object.keys( frontMatter )
		.filter( ( key ) => allowList.includes( key ) )
		.reduce( ( obj, key ) => {
			obj[ key ] = frontMatter[ key ];
			return obj;
		}, {} );
};
