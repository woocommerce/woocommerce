/**
 * External dependencies
 */
import { resolveSelect } from '@wordpress/data';
import debugFactory from 'debug';

const debug = debugFactory( 'woo-ai:product-editor:build-prompt' );

export async function buildProductTitleSuggestionsPromp( productId: number ) {
	const product = await resolveSelect( 'core' ).getEditedEntityRecord(
		'postType',
		'product',
		[ productId ]
	);

	if ( ! product ) {
		return '';
	}

	const {
		name,
		tags: tagsObject,
		categories: categoriesObject,
		attributes,
		type: product_type,
		downloadable: is_downloadable,
		virtual: is_virtual,
		short_description,
	} = product;

	const categories = categoriesObject.map(
		( { name: categoryName }: { name: string } ) => {
			return categoryName;
		}
	);

	const tags = tagsObject.map( ( { name: tagName }: { name: string } ) => {
		return tagName;
	} );

	const productPros = [];
	if ( name ) {
		productPros.push( `- Current Product Name: "${ name }"` );
	}
	if ( categories.length ) {
		productPros.push( `- Categories: "${ categories.join( '", "' ) }"` );
	}
	if ( tags.length ) {
		productPros.push( `- Tags: "${ tags.join( '", "' ) }"` );
	}
	if ( attributes.length ) {
		productPros.push( `- Attributes: "${ attributes.join( '", "' ) }"` );
	}
	if ( product_type ) {
		productPros.push( `- Product Type: "${ product_type }"` );
	}

	if ( short_description ) {
		productPros.push( `- Short Description: "${ short_description }"` );
	}

	if ( is_downloadable ) {
		productPros.push(
			`- Downloadable: ${ is_downloadable ? 'YES' : 'NO' }`
		);
	}
	if ( is_virtual ) {
		productPros.push( `- Virtual: ${ is_virtual ? 'YES' : 'NO' }` );
	}

	const productProsString = productPros.join( '\n' );

	const instructions = `
You are a WooCommerce SEO and marketing expert.
Using the product's name, description, tags, categories, and other attributes, provide three optimized alternatives to the product's title to enhance the store's SEO performance and sales.
Provide the best option for the product's title based on the product properties.
Identify the language used in the given title and use the same language in your response.
Product titles should contain at least 20 characters.
The product's properties are:
${ productProsString }

Important!: Respect the format of the response. The response should be an array of strings: [ "first-title", "second-title", "third-title" ]'
`;
	debug( 'instructions', instructions );

	return instructions;
}

export async function buildProductSummarySuggestionPromp( productId: number ) {
	const product = await resolveSelect( 'core' ).getEditedEntityRecord(
		'postType',
		'product',
		[ productId ]
	);

	if ( ! product ) {
		return '';
	}

	const {
		name,
		tags: tagsObject,
		categories: categoriesObject,
		attributes,
		type: product_type,
		downloadable: is_downloadable,
		virtual: is_virtual,
	} = product;

	const categories = categoriesObject.map(
		( { name: categoryName }: { name: string } ) => {
			return categoryName;
		}
	);

	const tags = tagsObject.map( ( { name: tagName }: { name: string } ) => {
		return tagName;
	} );

	const productPros = [];
	if ( name ) {
		productPros.push( `- Current Product Name: "${ name }"` );
	}
	if ( categories.length ) {
		productPros.push( `- Categories: "${ categories.join( '", "' ) }"` );
	}
	if ( tags.length ) {
		productPros.push( `- Tags: "${ tags.join( '", "' ) }"` );
	}
	if ( attributes.length ) {
		productPros.push( `- Attributes: "${ attributes.join( '", "' ) }"` );
	}
	if ( product_type ) {
		productPros.push( `- Product Type: "${ product_type }"` );
	}
	if ( is_downloadable ) {
		productPros.push(
			`- Downloadable: ${ is_downloadable ? 'YES' : 'NO' }`
		);
	}
	if ( is_virtual ) {
		productPros.push( `- Virtual: ${ is_virtual ? 'YES' : 'NO' }` );
	}

	const productProsString = productPros.join( '\n' );

	const instructions = `
You are a WooCommerce SEO and marketing expert.
Using the product's name, description, tags, categories, and other attributes, provide a summary for the product to enhance the store's SEO performance and sales.
Identify the language used in the given title and use the same language in your response.
The summary should contain at least 200 characters.
The product's properties are:
${ productProsString }

Important!: Respect the format of the response.
- The response should be a simple string.'
- Do not wrap the response in quotes.
`;
	debug( 'instructions', instructions );

	return instructions;
}
