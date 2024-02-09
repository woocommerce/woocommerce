const { test, expect } = require( '@playwright/test' );
const { admin } = require( '../../test-data/data' );
const { closeWelcomeModal } = require( '../../utils/editor' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;

const singleProductPrice1 = '10';
const singleProductPrice2 = '50';
const singleProductPrice3 = '200';

const simpleProductName = 'AAA Filter Products';

const productsFilteringPageTitle = 'Products Filtering';
const productsFilteringPageSlug = productsFilteringPageTitle
	.replace( / /gi, '-' )
	.toLowerCase();

let product1Id, product2Id, product3Id;

test.describe( 'Filter items in the shop by product price', () => {
	test.beforeAll( async ( { baseURL } ) => {
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
		// add products
		await api
			.post( 'products', {
				name: simpleProductName + ' 1',
				type: 'simple',
				regular_price: singleProductPrice1,
			} )
			.then( ( response ) => {
				product1Id = response.data.id;
			} );
		await api
			.post( 'products', {
				name: simpleProductName + ' 2',
				type: 'simple',
				regular_price: singleProductPrice2,
			} )
			.then( ( response ) => {
				product2Id = response.data.id;
			} );
		await api
			.post( 'products', {
				name: simpleProductName + ' 3',
				type: 'simple',
				regular_price: singleProductPrice3,
			} )
			.then( ( response ) => {
				product3Id = response.data.id;
			} );
	} );

	test.afterAll( async ( { baseURL } ) => {
		const api = new wcApi( {
			url: baseURL,
			consumerKey: process.env.CONSUMER_KEY,
			consumerSecret: process.env.CONSUMER_SECRET,
			version: 'wc/v3',
		} );
		await api.post( 'products/batch', {
			delete: [ product1Id, product2Id, product3Id ],
		} );
	} );

	test( 'can create a new page with products filter and all products', async ( {
		page,
	} ) => {
		await page.goto( 'wp-admin/post-new.php?post_type=page' );
		await page.waitForLoadState( 'networkidle' );
		await page.locator( 'input[name="log"]' ).fill( admin.username );
		await page.locator( 'input[name="pwd"]' ).fill( admin.password );
		await page.locator( 'text=Log In' ).click();

		await closeWelcomeModal( { page } );

		await page
			.getByRole( 'textbox', { name: 'Add title' } )
			.fill( productsFilteringPageTitle );
		await page.getByRole( 'button', { name: 'Add default block' } ).click();
		await page
			.getByRole( 'document', {
				name: 'Empty block; start writing or type forward slash to choose a block',
			} )
			.fill( '/filter' );
		await page
			.getByRole( 'option' )
			.filter( { hasText: 'Filter by Price' } )
			.click();

		await page.getByRole( 'textbox', { name: 'Add title' } ).click();
		await page.getByLabel( 'Add block' ).click();
		await page
			.getByPlaceholder( 'Search', { exact: true } )
			.fill( 'products' );
		await page
			.getByRole( 'option' )
			.filter( { hasText: 'All Products' } )
			.click();

		await page
			.getByRole( 'button', { name: 'Publish', exact: true } )
			.click();
		await page
			.getByRole( 'region', { name: 'Editor publish' } )
			.getByRole( 'button', { name: 'Publish', exact: true } )
			.click();
		await expect(
			page.getByText( `${ productsFilteringPageTitle } is now live.` )
		).toBeVisible();
	} );

	test( 'filter products by prices on the created page', async ( {
		page,
	} ) => {
		// go to the page to test filtering products by price
		await page.goto( productsFilteringPageSlug );
		await expect(
			page.getByRole( 'heading', { name: productsFilteringPageTitle } )
		).toBeVisible();

		// filter by maximum $50 and verify the results
		await page
			.getByRole( 'textbox', { name: 'Filter products by maximum' } )
			.fill( '$50' );
		await page
			.getByRole( 'heading', {
				name: productsFilteringPageTitle,
			} )
			.click();

		await expect(
			page
				.locator( 'h2.wc-block-grid__product-title' )
				.filter( { hasText: `${ simpleProductName } 1` } )
		).toBeVisible();
		await expect(
			page
				.locator( 'h2.wc-block-grid__product-title' )
				.filter( { hasText: `${ simpleProductName } 2` } )
		).toBeVisible();
		await expect(
			page
				.locator( 'h2.wc-block-grid__product-title' )
				.filter( { hasText: `${ simpleProductName } 3` } )
		).toBeHidden();

		// filter by between $100 and $200 and verify the results
		await page
			.getByRole( 'textbox', { name: 'Filter products by maximum' } )
			.fill( '$200' );
		await page
			.getByRole( 'textbox', { name: 'Filter products by minimum' } )
			.fill( '$100' );
		await page
			.getByRole( 'heading', {
				name: productsFilteringPageTitle,
			} )
			.click();

		await expect(
			page
				.locator( 'h2.wc-block-grid__product-title' )
				.filter( { hasText: `${ simpleProductName } 1` } )
		).toBeHidden();
		await expect(
			page
				.locator( 'h2.wc-block-grid__product-title' )
				.filter( { hasText: `${ simpleProductName } 2` } )
		).toBeHidden();
		await expect(
			page
				.locator( 'h2.wc-block-grid__product-title' )
				.filter( { hasText: `${ simpleProductName } 3` } )
		).toBeVisible();
	} );
} );
