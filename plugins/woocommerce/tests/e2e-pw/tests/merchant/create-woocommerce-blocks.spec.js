const { test: baseTest, expect } = require( '../../fixtures/fixtures' );
const {
	goToPageEditor,
	fillPageTitle,
	insertBlock,
	getCanvas,
	publishPage,
} = require( '../../utils/editor' );

const simpleProductName = 'Simplest Product';
const singleProductPrice = '555.00';

// all WooCommerce blocks except:
// default cart and checkout blocks, mini-cart
const blocks = [
	'All Products',
	'All Reviews',
	'Active Filters',
	'Best Selling Products',
	'Coming soon',
	'Customer account',
	'Featured Category',
	'Featured Product',
	'Filter by Price',
	'Filter by Stock',
	'Filter by Attribute',
	'Filter by Rating',
	'Hand-picked Products',
	'Newest Products',
	'On Sale Products',
	'Product Categories List',
	'Product Collection',
	'Product Gallery (Beta)',
	'Product Search',
	'Products by Attribute',
	'Products by Category',
	'Products by Tag',
	'Reviews by Category',
	'Reviews by Product',
	'Single Product',
	'Store Notices',
	'Top Rated Products',
];

let productId, shippingZoneId, productTagId, attributeId, productCategoryId;

baseTest.describe( 'Add WooCommerce Blocks Into Page', () => {
	const test = baseTest.extend( {
		storageState: process.env.ADMINSTATE,
		testPageTitlePrefix: 'Woocommerce Blocks',
	} );

	test.beforeAll( async ( { api } ) => {
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

	test.afterAll( async ( { api } ) => {
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

	test( `can insert all WooCommerce blocks into page`, async ( {
		page,
		testPage,
	} ) => {
		await goToPageEditor( { page } );

		await fillPageTitle( page, testPage.title );

		for ( let i = 0; i < blocks.length; i++ ) {
			await test.step( `Insert ${ blocks[ i ] } block`, async () => {
				await insertBlock( page, blocks[ i ] );

				const canvas = await getCanvas( page );

				// eslint-disable-next-line playwright/no-conditional-in-test
				if ( blocks[ i ] === 'Reviews by Product' ) {
					await canvas.getByLabel( simpleProductName ).check();
					await canvas
						.getByRole( 'button', { name: 'Done', exact: true } )
						.click();
				}

				// verify added blocks into page
				await expect(
					canvas
						.getByRole( 'document', {
							name: `Block: ${ blocks[ i ] }`,
							exact: true,
						} )
						.first()
				).toBeVisible();
			} );
		}

		await publishPage( page, testPage.title );

		// check all blocks inside the page after publishing
		// except the product price due to invisibility and false-positive
		const canvas = await getCanvas( page );
		for ( let i = 1; i < blocks.length; i++ ) {
			// verify added blocks into page
			await expect(
				canvas
					.getByRole( 'document', {
						name: `Block: ${ blocks[ i ] }`,
						exact: true,
					} )
					.first()
			).toBeVisible();
		}
	} );
} );
