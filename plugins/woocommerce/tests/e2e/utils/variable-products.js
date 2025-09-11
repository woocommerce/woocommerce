const api = require( './api' );

/**
 * Array to hold all product ID's to be deleted after test.
 */
const productIds = [];

/**
 * The `attributes` property to be used in the request payload for creating a variable product through the REST API.
 *
 * @see {@link [Product - Attributes properties](https://woocommerce.github.io/woocommerce-rest-api-docs/#product-attributes-properties)}
 */
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

/**
 * Request payload for creating variations.
 *
 * @see {@link [Product variation properties](https://woocommerce.github.io/woocommerce-rest-api-docs/#product-variation-properties)}
 */
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

/**
 * Create a variable product using the WooCommerce REST API.
 *
 * @param {{ name: string, visible: boolean, variation: boolean, options: string[] }[]} attributes List of attributes. See [Product - Attributes properties](https://woocommerce.github.io/woocommerce-rest-api-docs/#product-attributes-properties).
 * @returns {Promise<number>} ID of the created variable product
 */
async function createVariableProduct( attributes = [] ) {
	const randomNum = Math.floor( Math.random() * 1000 );
	const payload = {
		name: `Unbranded Granite Shirt ${ randomNum }`,
		type: 'variable',
		attributes,
	};

	const productId = await api.create.product( payload );

	productIds.push( productId );

	return productId;
}

/**
 * Clean up all products created by the test.
 */
async function deleteProductsAddedByTests() {
	await api.deletePost.products( productIds );
}

/**
 * Enable or disable the variable product tour through JavaScript.
 *
 * @param {import('@playwright/test').Browser} browser
 * @param {boolean} show Whether to show the variable product tour or not.
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

/**
 * Generate all possible variations from the given attributes.
 *
 * @param {{ name: string, visible: boolean, variation: boolean, options: string[] }[]} attributes
 * @returns All possible variations from the given attributes
 */
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

/**
 * Create variations through the WooCommerce REST API.
 *
 * @param {number} productId Product ID to add variations to.
 * @param {{regular_price: string, attributes: {name: string, option: string}[]}[]} variations List of variations to create.
 * @returns {Promise<number[]>} Array of variation ID's created.
 */
async function createVariations( productId, variations ) {
	return await api.create.productVariations( productId, variations );
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
