/**
 * External dependencies
 */
import { resolveSelect } from '@wordpress/data';
export async function buildProductTitleSuggestionsPromp( productId: number ) {
	const product = await resolveSelect( 'core' ).getEntityRecord(
		'postType',
		'product',
		[ productId ]
	);

	if ( ! product ) {
		return '';
	}

	const {
		name,
		tags,
		categories,
		attributes,
		type: product_type,
		downloadable: is_downloadable,
		virtual: is_virtual,
	} = product;

	const validProductData = Object.entries( {
		name,
		tags,
		categories,
		attributes,
		product_type,
		is_downloadable,
		is_virtual,
	} ).reduce( ( acc, [ key, value ] ) => {
		if (
			typeof value === 'boolean' ||
			( value instanceof Array
				? Boolean( value.length )
				: Boolean( value ) )
		) {
			// eslint-disable-next-line @typescript-eslint/ban-ts-comment
			// @ts-ignore
			acc[ key ] = value;
		}
		return acc;
	}, {} );

	const instructions = [
		'You are a WooCommerce SEO and marketing expert.',
		"Using the product's name, description, tags, categories, and other attributes, provide three optimized alternatives to the product's title to enhance the store's SEO performance and sales.",
		"Provide the best option for the product's title based on the product properties.",
		'Identify the language used in the given title and use the same language in your response.',
		'Product titles should contain at least 20 characters.',
		"The product's properties are:",
		`${ JSON.stringify( validProductData ) }`,
		'Here is an example of a valid response:',
		'[ "first-title", "second-title", "third-title" ]',
	];

	return instructions.join( '\n' );
}
