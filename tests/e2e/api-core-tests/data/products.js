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
