const { test: baseTest, expect } = require( '../../fixtures/fixtures' );
const {
	goToPageEditor,
	fillPageTitle,
	insertBlock,
	insertBlockByShortcut,
	publishPage,
} = require( '../../utils/editor' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;

const singleProductPrice1 = '10';
const singleProductPrice2 = '50';
const singleProductPrice3 = '200';

const simpleProductName = 'AAA Filter Products';

let product1Id, product2Id, product3Id;

baseTest.describe( 'Filter items in the shop by product price', () => {
	const test = baseTest.extend( {
		storageState: process.env.ADMINSTATE,
		testPageTitlePrefix: 'Products filter',
	} );

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

	test( 'filter products by prices on the created page', async ( {
		page,
		testPage,
	} ) => {
		const sortingProductsDropdown = '.wc-block-sort-select__select';

		await goToPageEditor( { page } );
		await fillPageTitle( page, testPage.title );
		await insertBlockByShortcut( page, '/filter' );
		await insertBlock( page, 'All Products' );
		await publishPage( page, testPage.title );

		// go to the page to test filtering products by price
		await page.goto( testPage.slug );
		await expect(
			page.getByRole( 'heading', { name: testPage.title } )
		).toBeVisible();

		// The price filter input is initially enabled, but it becomes disabled
		// for the time it takes to fetch the data. To avoid setting the filter
		// value before the input is properly initialized, we wait for the input
		// to be disabled first. This is a safeguard to avoid flakiness which
		// should be addressed in the code
		await page
			.getByRole( 'textbox', {
				name: 'Filter products by maximum price',
				disabled: true,
			} )
			.waitFor( { timeout: 3000 } )
			.catch( () => {
				// Do not throw in case Playwright doesn't make it in time for the
				// initial (pre-request) render.
			} );

		// filter by maximum $50 and verify the results
		await page
			.getByRole( 'textbox', { name: 'Filter products by maximum' } )
			.fill( '$50' );
		// click and sort products to allow changes to take effect
		await page.locator( sortingProductsDropdown ).click();
		await page
			.locator( sortingProductsDropdown )
			.selectOption( 'Popularity' );
		// to avoid flakiness
		await page
			.getByRole( 'textbox', {
				name: 'Filter products by maximum price',
				disabled: true,
			} )
			.waitFor( { timeout: 3000 } )
			.catch( () => {
				// Do not throw in case Playwright doesn't make it in time for the
				// initial (pre-request) render.
			} );

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
		// click and sort products to allow changes to take effect
		await page.locator( sortingProductsDropdown ).click();
		await page
			.locator( sortingProductsDropdown )
			.selectOption( 'Default sorting' );
		// to avoid flakiness
		await page
			.getByRole( 'textbox', {
				name: 'Filter products by maximum price',
				disabled: true,
			} )
			.waitFor( { timeout: 3000 } )
			.catch( () => {
				// Do not throw in case Playwright doesn't make it in time for the
				// initial (pre-request) render.
			} );
		await page
			.getByRole( 'textbox', { name: 'Filter products by minimum' } )
			.fill( '$100' );
		// click and sort products to allow changes to take effect
		await page.locator( sortingProductsDropdown ).click();
		await page.locator( sortingProductsDropdown ).selectOption( 'Latest' );
		// to avoid flakiness
		await page
			.getByRole( 'textbox', {
				name: 'Filter products by maximum price',
				disabled: true,
			} )
			.waitFor( { timeout: 3000 } )
			.catch( () => {
				// Do not throw in case Playwright doesn't make it in time for the
				// initial (pre-request) render.
			} );

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
