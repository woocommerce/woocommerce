const { test, expect } = require( '@playwright/test' );
const { disableWelcomeModal } = require( '../../utils/editor' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;

const allWooBlocksPageTitle = `Insert All Woo Blocks ${ Date.now() }`;

const simpleProductName = 'Simplest Product';
const singleProductPrice = '555.00';

// all WooCommerce blocks except:
// default cart and checkout blocks, mini-cart, product collection (beta)
const blocks = [
	{
		name: 'Product Price',
	},
	{
		name: 'Product Search',
	},
	{
		name: 'Reviews by Product',
	},
	{
		name: 'Single Product',
	},
	{
		name: 'All Products',
	},
	{
		name: 'All Reviews',
	},
	{
		name: 'Active Filters',
	},
	{
		name: 'Filter by Price',
	},
	{
		name: 'Filter by Stock',
	},
	{
		name: 'Filter by Attribute',
	},
	{
		name: 'Filter by Rating',
	},
	{
		name: 'Hand-picked Products',
	},
	{
		name: 'Products by Category',
	},
	{
		name: 'Newest Products',
	},
	{
		name: 'Products by Tag',
	},
	{
		name: 'Top Rated Products',
	},
	{
		name: 'Customer account',
	},
	{
		name: 'Featured Category',
	},
	{
		name: 'Featured Product',
	},
	{
		name: 'Store Notices',
	},
	{
		name: 'Best Selling Products',
	},
	{
		name: 'Product Categories List',
	},
	{
		name: 'On Sale Products',
	},
	{
		name: 'Reviews by Category',
	},
];

let productId, shippingZoneId, productTagId, attributeId, productCategoryId;

test.describe( 'Insert All WooCommerce Blocks Into Page', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	test.beforeAll( async ( { baseURL } ) => {
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
		// add product attribute
		await api
			.post( 'products/attributes', {
				name: 'testattribute',
				has_archives: true,
			} )
			.then( ( response ) => {
				attributeId = response.data.id;
			} );
		// add product attribute term
		await api.post( `products/attributes/${ attributeId }/terms`, {
			name: 'attributeterm',
		} );
		// add product categories
		await api
			.post( 'products/categories', {
				name: 'simple category',
			} )
			.then( ( response ) => {
				productCategoryId = response.data.id;
			} );
		// add product tags
		await api
			.post( 'products/tags', {
				name: 'simpletag',
			} )
			.then( ( response ) => {
				productTagId = response.data.id;
			} );
		// add product
		await api
			.post( 'products', {
				name: simpleProductName,
				type: 'simple',
				regular_price: singleProductPrice,
				categories: [
					{
						id: productCategoryId,
						name: 'simple category',
					},
				],
				average_rating: 5.0,
				rating_count: 2,
				featured: true,
				tags: [ { id: productTagId } ],
				attributes: [
					{
						id: attributeId,
						visible: true,
						options: [ 'attributeterm' ],
					},
				],
			} )
			.then( ( response ) => {
				productId = response.data.id;
			} );
		// add shipping zone and method
		await api
			.post( 'shipping/zones', {
				name: 'Shipping Zone',
			} )
			.then( ( response ) => {
				shippingZoneId = response.data.id;
			} );
		await api.post( `shipping/zones/${ shippingZoneId }/methods`, {
			method_id: 'free_shipping',
		} );
	} );

	test.afterAll( async ( { baseURL } ) => {
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
		await api.delete( `products/${ productId }`, {
			force: true,
		} );
		await api.post( 'products/tags/batch', {
			delete: [ productTagId ],
		} );
		await api.post( 'products/attributes/batch', {
			delete: [ attributeId ],
		} );
		await api.post( 'products/categories/batch', {
			delete: [ productCategoryId ],
		} );
		await api.delete( `shipping/zones/${ shippingZoneId }`, {
			force: true,
		} );
	} );

	test( `can insert all WooCommerce blocks into page`, async ( { page } ) => {
		// go to create a new page
		await page.goto( 'wp-admin/post-new.php?post_type=page' );

		await disableWelcomeModal( { page } );

		// fill page title
		await page
			.getByRole( 'textbox', { name: 'Add title' } )
			.fill( allWooBlocksPageTitle );

		// add all WC blocks and verify them as added into page
		for ( let i = 0; i < blocks.length; i++ ) {
			// click title field for block inserter to show up
			await page.getByRole( 'textbox', { name: 'Add title' } ).click();

			// add block into page
			await page.getByLabel( 'Add block' ).click();
			await page
				.getByPlaceholder( 'Search', { exact: true } )
				.fill( blocks[ i ].name );
			await page
				.getByRole( 'option', { name: blocks[ i ].name, exact: true } )
				.click();

			if ( blocks[ i ].name === 'Reviews by Product' ) {
				await page.getByLabel( simpleProductName ).check();
				await page
					.getByRole( 'button', { name: 'Done', exact: true } )
					.click();
			}

			// verify added blocks into page
			await expect(
				page
					.getByRole( 'document', {
						name: `Block: ${ blocks[ i ].name }`,
						exact: true,
					} )
					.first()
			).toBeVisible();
		}

		// save and publish the page
		await page
			.getByRole( 'button', { name: 'Publish', exact: true } )
			.click();
		await page
			.getByRole( 'region', { name: 'Editor publish' } )
			.getByRole( 'button', { name: 'Publish', exact: true } )
			.click();
		await expect(
			page.getByText( `${ allWooBlocksPageTitle } is now live.` )
		).toBeVisible();

		// check all blocks inside the page after publishing
		// except the product price due to invisibility and false-positive
		for ( let i = 1; i < blocks.length; i++ ) {
			// verify added blocks into page
			await expect(
				page
					.getByRole( 'document', {
						name: `Block: ${ blocks[ i ].name }`,
						exact: true,
					} )
					.first()
			).toBeVisible();
		}
	} );
} );
