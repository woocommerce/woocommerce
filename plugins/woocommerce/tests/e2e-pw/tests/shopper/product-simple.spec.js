const { test, expect } = require( '@playwright/test' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;

const productPrice = '18.16';
const simpleProductName = 'Simple single product';
const simpleProductSlug = simpleProductName.replace( / /gi, '-' ).toLowerCase();

let simpleProductId, productCategory1Id, productCategory2Id;

test.describe( 'Single Product Page', () => {
	const productCategoryName1 = 'Hoodies';
	const productCategoryName2 = 'Jumpers';
	const productDescription =
		'<h2>Custom Heading</h2>' +
		'<p>This is sample text.</p> </br>' +
		'<b>This should be bolded</b> </br>' +
		'<em>This should be italic</em> </br>' +
		'<blockquote>This should be quoted</blockquote>' +
		'<ul><li>Test bulletted item</li></ul>' +
		'<ol><li>Test numbered item</li></ol>';

	test.beforeAll( async ( { baseURL } ) => {
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );

		// add product categories
		await api
			.post( 'products/categories', {
				name: productCategoryName1,
			} )
			.then( ( response ) => {
				productCategory1Id = response.data.id;
			} );
		await api
			.post( 'products/categories', {
				name: productCategoryName2,
			} )
			.then( ( response ) => {
				productCategory2Id = response.data.id;
			} );

		// add products
		await api
			.post( 'products', {
				name: simpleProductName,
				description: productDescription,
				type: 'simple',
				categories: [
					{
						id: productCategory1Id,
						name: productCategoryName1,
					},
				],
				images: [
					{
						src: 'http://demo.woothemes.com/woocommerce/wp-content/uploads/sites/56/2013/06/T_2_front.jpg',
					},
					{
						src: 'http://demo.woothemes.com/woocommerce/wp-content/uploads/sites/56/2013/06/T_2_back.jpg',
					},
					{
						src: 'http://demo.woothemes.com/woocommerce/wp-content/uploads/sites/56/2013/06/T_3_front.jpg',
					},
				],
				regular_price: productPrice,
			} )
			.then( ( response ) => {
				simpleProductId = response.data.id;
			} );
	} );

	test.beforeEach( async ( { context } ) => {
		// Shopping cart is very sensitive to cookies, so be explicit
		await context.clearCookies();
	} );

	test.afterAll( async ( { baseURL } ) => {
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
		await api.post( 'products/batch', {
			delete: [ simpleProductId ],
		} );
		await api.post( 'products/categories/batch', {
			delete: [ productCategory1Id, productCategory2Id ],
		} );
	} );

	test( 'should be able to post a review and see it after', async ( {
		page,
	} ) => {
		await page.goto( 'my-account' );
		await page.locator( '#username' ).fill( 'admin' );
		await page.locator( '#password' ).fill( 'password' );
		await page.locator( 'text=Log in' ).click();

		await page.goto( `product/${ simpleProductSlug }` );

		await expect( page.locator( '.reviews_tab' ) ).toContainText(
			'Reviews (0)'
		);
		await page.locator( '.reviews_tab' ).click();
		await page.locator( '.star-4' ).click();
		await page.locator( '#comment' ).fill( 'This product is great!' );
		await page.locator( 'text=Submit' ).click();
		await expect(
			page.locator( '.woocommerce-Reviews-title' )
		).toContainText( `1 review for ${ simpleProductName }` );
		await expect( page.locator( '.reviews_tab' ) ).toContainText(
			'Reviews (1)'
		);
	} );

	test( 'should be able to see product description', async ( { page } ) => {
		await page.goto( `product/${ simpleProductSlug }` );

		expect( await page.title() ).toBe(
			simpleProductName + ' – WooCommerce Core E2E Test Suite'
		);

		await page.getByRole( 'tab', { name: 'Description' } ).click();

		await expect(
			page
				.getByRole( 'tabpanel' )
				.filter( 'heading', { name: 'Custom Heading' } )
		).toBeVisible();
		await expect(
			page
				.getByRole( 'tabpanel' )
				.filter( 'paragraph', { name: 'This is sample text.' } )
		).toBeVisible();
		await expect(
			page
				.getByRole( 'tabpanel' )
				.filter( 'strong', { name: 'This should be bolded' } )
		).toBeVisible();
		await expect(
			page
				.getByRole( 'tabpanel' )
				.filter( 'emphasis', { name: 'This should be italic' } )
		).toBeVisible();
		await expect(
			page
				.getByRole( 'tabpanel' )
				.filter( 'blockquote', { name: 'This should be quoted' } )
		).toBeVisible();
		await expect(
			page
				.getByRole( 'tabpanel' )
				.filter( 'listitem', { name: 'Test bulletted item' } )
		).toBeVisible();
		await expect(
			page
				.getByRole( 'tabpanel' )
				.filter( 'listitem', { name: 'Test numbered item' } )
		).toBeVisible();
	} );
} );
