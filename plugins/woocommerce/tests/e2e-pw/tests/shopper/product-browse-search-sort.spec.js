const { test, expect } = require( '@playwright/test' );
const wcApi = require( '@woocommerce/woocommerce-rest-api' ).default;

const singleProductPrice1 = '979.99';
const singleProductPrice2 = '989.99';
const singleProductPrice3 = '999.99';

const simpleProductName = 'AAA Search and Browse Product';

const categoryA = 'Dogs';
const categoryB = 'Cats';
const categoryC = 'Fish';

let categoryAId, categoryBId, categoryCId, product1Id, product2Id, product3Id;

test.describe(
	'Search, browse by categories and sort items in the shop',
	() => {
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
					name: categoryA,
				} )
				.then( ( response ) => {
					categoryAId = response.data.id;
				} );
			await api
				.post( 'products/categories', {
					name: categoryB,
				} )
				.then( ( response ) => {
					categoryBId = response.data.id;
				} );
			await api
				.post( 'products/categories', {
					name: categoryC,
				} )
				.then( ( response ) => {
					categoryCId = response.data.id;
				} );

			// add products
			await api
				.post( 'products', {
					name: simpleProductName + ' 1',
					type: 'simple',
					regular_price: singleProductPrice1,
					categories: [ { id: categoryAId } ],
				} )
				.then( ( response ) => {
					product1Id = response.data.id;
				} );
			await api
				.post( 'products', {
					name: simpleProductName + ' 2',
					type: 'simple',
					regular_price: singleProductPrice2,
					categories: [ { id: categoryBId } ],
				} )
				.then( ( response ) => {
					product2Id = response.data.id;
				} );
			await api
				.post( 'products', {
					name: simpleProductName + ' 3',
					type: 'simple',
					regular_price: singleProductPrice3,
					categories: [ { id: categoryCId } ],
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
			await api.post( 'products/categories/batch', {
				delete: [ categoryAId, categoryBId, categoryCId ],
			} );
		} );

		test( 'should let user search the store', async ( { page } ) => {
			await page.goto( 'shop/' );

			await page.fill(
				'#wp-block-search__input-1',
				simpleProductName + ' 1'
			);
			await page.click( 'button.wp-block-search__button' );

			await expect( page.locator( 'h1.page-title' ) ).toContainText(
				`${ simpleProductName } 1`
			);
			await expect( page.locator( 'h2.entry-title' ) ).toContainText(
				simpleProductName + ' 1'
			);
		} );

		test( 'should let user browse products by categories', async ( {
			page,
		} ) => {
			// browse the Audio category
			await page.goto( 'shop/' );
			await page.click( `text=${ simpleProductName } 2` );
			await page.click( 'span.posted_in > a', { hasText: categoryB } );

			// verify the Audio category page
			await expect( page.locator( 'h1.page-title' ) ).toContainText(
				categoryB
			);
			await expect(
				page.locator( 'h2.woocommerce-loop-product__title' )
			).toContainText( simpleProductName + ' 2' );
			await page.click( `text=${ simpleProductName } 2` );
			await expect( page.locator( 'h1.entry-title' ) ).toContainText(
				simpleProductName + ' 2'
			);
		} );

		test( 'should let user sort the products in the shop', async ( {
			page,
		} ) => {
			await page.goto( 'shop/' );

			// sort by price high to low
			await page.selectOption( '.orderby', 'price-desc' );
			// last product is most expensive
			await expect(
				page.locator( 'ul.products > li:nth-child(1)' )
			).toContainText( `${ simpleProductName } 3` );
			await expect(
				page.locator( 'ul.products > li:nth-child(3)' )
			).toContainText( `${ simpleProductName } 1` );

			// sort by price low to high
			await page.selectOption( '.orderby', 'price' );
			// last product is most expensive
			await expect(
				page.locator( 'ul.products > li:nth-last-child(3)' )
			).toContainText( `${ simpleProductName } 1` );
			await expect(
				page.locator( 'ul.products > li:nth-last-child(1)' )
			).toContainText( `${ simpleProductName } 3` );
		} );
	}
);
