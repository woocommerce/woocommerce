/**
 * External dependencies
 */
require( 'dotenv' ).config();
const { USER_KEY, USER_SECRET, API_PATH } = process.env;

/**
 * Internal dependencies
 */
 const {
	HTTPClientFactory,
} = require( '@woocommerce/api' );

const httpClient = HTTPClientFactory
	.build( API_PATH )
	.withBasicAuth( USER_KEY, USER_SECRET )
	.withIndexPermalinks()
	.create();

const createProductCategory = ( data ) => httpClient.post( '/wc/v3/products/categories', data );
const createProductAttribute = ( name ) => httpClient.post( '/wc/v3/products/attributes', { name } );
const createProductAttributeTerms = ( parentId, termNames ) => httpClient.post(
	`/wc/v3/products/attributes/${ parentId }/terms/batch`,
	{
		create: termNames.map( name => ( { name } ) )
	}
);

const createSampleCategories = async () => {
	const { data: clothing } = await createProductCategory( { name: 'Clothing' } );
	const { data: accessories } = await createProductCategory( { name: 'Accessories', parent: clothing.id } );
	const { data: hoodies } = await createProductCategory( { name: 'Hoodies', parent: clothing.id } );
	const { data: tshirts } = await createProductCategory( { name: 'Tshirts', parent: clothing.id } );
	const { data: decor } = await createProductCategory( { name: 'Decor' } );
	const { data: music } = await createProductCategory( { name: 'Music' } );

	return {
		clothing,
		accessories,
		hoodies,
		tshirts,
		decor,
		music,
	}
};

const createSampleAttributes = async () => {
	const { data: color } = await createProductAttribute( 'Color' );
	const { data: size } = await createProductAttribute( 'Size' );
	await createProductAttributeTerms( color.id, [ 'Blue', 'Gray', 'Green', 'Red', 'Yellow' ] );
	await createProductAttributeTerms( size.id, [ 'Large', 'Medium', 'Small' ] );

	return {
		color,
		size,
	}
};
