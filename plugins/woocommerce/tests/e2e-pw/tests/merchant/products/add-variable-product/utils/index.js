const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;

const productAttributes = [
	{
		name: 'Colour',
		visible: true,
		variation: true,
		options: [ 'Red', 'Green' ],
	},
	{
		name: 'Size',
		visible: true,
		variation: true,
		options: [ 'Small', 'Medium' ],
	},
	{
		name: 'Logo',
		visible: true,
		variation: true,
		options: [ 'Woo', 'WordPress' ],
	},
];

const sampleVariations = [
	{
		regular_price: '9.99',
		attributes: [
			{
				name: 'Colour',
				option: 'Red',
			},
			{
				name: 'Size',
				option: 'Small',
			},
			{
				name: 'Logo',
				option: 'Woo',
			},
		],
	},
	{
		regular_price: '10.99',
		attributes: [
			{
				name: 'Colour',
				option: 'Red',
			},
			{
				name: 'Size',
				option: 'Small',
			},
			{
				name: 'Logo',
				option: 'WordPress',
			},
		],
	},
	{
		regular_price: '11.99',
		attributes: [
			{
				name: 'Colour',
				option: 'Red',
			},
			{
				name: 'Size',
				option: 'Medium',
			},
			{
				name: 'Logo',
				option: 'Woo',
			},
		],
	},
];

function newWCApi( baseURL ) {
	return new wcApi( {
		url: baseURL,
		consumerKey: process.env.CONSUMER_KEY,
		consumerSecret: process.env.CONSUMER_SECRET,
		version: 'wc/v3',
	} );
}

async function createVariableProduct( baseURL, attributes = [] ) {
	const api = newWCApi( baseURL );
	const randomNum = Math.floor( Math.random() * 1000 );
	const payload = {
		name: `Unbranded Granite Shirt ${ randomNum }`,
		type: 'variable',
		attributes,
	};

	const productId = await api
		.post( 'products', payload )
		.then( ( response ) => response.data.id );

	return productId;
}

async function deleteProductsAddedByTests( baseURL, productIds ) {
	const api = newWCApi( baseURL );

	await api.post( 'products/batch', { delete: productIds } );
}

/**
 * @param {import('@playwright/test').Browser} browser
 */
async function showVariableProductTour( browser, show ) {
	const productPageURL = 'wp-admin/post-new.php?post_type=product';
	const addProductPage = await browser.newPage();

	// Go to "Add new product" page
	await addProductPage.goto( productPageURL );

	// Get the current user's ID and user preferences
	const { id: userId, woocommerce_meta } = await addProductPage.evaluate(
		() => {
			return window.wp.data.select( 'core' ).getCurrentUser();
		}
	);

	// Turn off the variable product tour
	const updatedWooCommerceMeta = {
		...woocommerce_meta,
		variable_product_tour_shown: show ? '' : '"yes"',
	};

	// Save the updated user preferences
	await addProductPage.evaluate(
		async ( { userId, updatedWooCommerceMeta } ) => {
			await window.wp.data.dispatch( 'core' ).saveUser( {
				id: userId,
				woocommerce_meta: updatedWooCommerceMeta,
			} );
		},
		{ userId, updatedWooCommerceMeta }
	);

	// Close the page
	await addProductPage.close();
}

function generateVariationsFromAttributes( attributes ) {
	const combine = ( runningList, nextAttribute ) => {
		const variations = [];
		let newVar;

		if ( ! Array.isArray( runningList[ 0 ] ) ) {
			runningList = [ runningList ];
		}

		for ( const partialVariation of runningList ) {
			if ( runningList.length === 1 ) {
				for ( const startingAttribute of partialVariation ) {
					for ( const nextAttrValue of nextAttribute ) {
						newVar = [ startingAttribute, nextAttrValue ];
						variations.push( newVar );
					}
				}
			} else {
				for ( const nextAttrValue of nextAttribute ) {
					newVar = partialVariation.concat( [ nextAttrValue ] );
					variations.push( newVar );
				}
			}
		}

		return variations;
	};

	let allVariations = attributes[ 0 ].options;

	for ( let i = 1; i < attributes.length; i++ ) {
		const nextAttribute = attributes[ i ].options;

		allVariations = combine( allVariations, nextAttribute );
	}

	return allVariations;
}

async function createVariations( baseURL, productId, variations ) {
	const api = newWCApi( baseURL );

	const response = await api.post(
		`products/${ productId }/variations/batch`,
		{
			create: variations,
		}
	);

	return response.data.create.map( ( { id } ) => id );
}

module.exports = {
	createVariableProduct,
	deleteProductsAddedByTests,
	generateVariationsFromAttributes,
	productAttributes,
	showVariableProductTour,
	sampleVariations,
	createVariations,
};
