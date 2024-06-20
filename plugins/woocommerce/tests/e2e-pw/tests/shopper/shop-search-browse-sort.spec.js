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

test.describe( 'Search, browse by categories and sort items in the shop', () => {
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

	// default theme doesn't have a search box, but can simulate a search by visiting the search URL
	test( 'should let user search the store', async ( { page } ) => {
		await test.step( 'Go to the shop and perform the search', async () => {
			await page.goto( `shop/?s=${ simpleProductName }%201` );

			await expect(
				page.getByRole( 'heading', {
					name: `${ simpleProductName } 1`,
				} )
			).toBeVisible();
			await expect( page.getByLabel( 'Breadcrumb' ) ).toContainText(
				`${ simpleProductName } 1`
			);
		} );
	} );

	test( 'should let user browse products by categories', async ( {
		page,
	} ) => {
		await test.step( 'Go to the shop and browse by the Audio category', async () => {
			await page.goto( 'shop/' );
			await page.locator( `text=${ simpleProductName } 2` ).click();
			await page
				.getByLabel( 'Breadcrumb' )
				.getByRole( 'link', { name: categoryB } )
				.click();
		} );

		await test.step( 'Ensure the Audio category page contains all the relevant products', async () => {
			await expect(
				page.getByRole( 'heading', { name: categoryB } )
			).toBeVisible();
			await expect(
				page.getByRole( 'heading', { name: simpleProductName + ' 2' } )
			).toBeVisible();
			await page.locator( `text=${ simpleProductName } 2` ).click();
			await expect(
				page.getByRole( 'heading', { name: simpleProductName + ' 2' } )
			).toBeVisible();
		} );
	} );

	test( 'should let user sort the products in the shop', async ( {
		page,
	} ) => {
		await test.step( 'Go to the shop and sort by price high to low', async () => {
			await page.goto( 'shop/' );
			await expect(
				page.getByLabel( `Add to cart: “${ simpleProductName } 1”` )
			).toBeVisible();

			// sort by price high to low
			await page.getByLabel( 'Shop order' ).selectOption( 'price-desc' );
			await page.waitForURL( /.*?orderby=price-desc.*/ );

			await expect(
				page.getByText( 'Add to cart View cart' ).nth( 2 )
			).toBeVisible();

			// Check that the priciest appears before the cheapest in the list
			const highToLowList = await page
				.getByRole( 'listitem' )
				.getByRole( 'heading' )
				.allInnerTexts();
			const highToLow_index_priciest = highToLowList.indexOf(
				`${ simpleProductName } 3`
			);
			const highToLow_index_cheapest = highToLowList.indexOf(
				`${ simpleProductName } 1`
			);
			expect( highToLow_index_priciest ).toBeLessThan(
				highToLow_index_cheapest
			);
		} );

		await test.step( 'Go to the shop and sort by price low to high', async () => {
			await page.goto( 'shop/' );
			await expect(
				page.getByLabel( `Add to cart: “${ simpleProductName } 1”` )
			).toBeVisible();

			// sort by price low to high
			await page.getByLabel( 'Shop order' ).selectOption( 'price' );
			await page.waitForURL( /.*?orderby=price.*/ );

			await expect(
				page.getByText( 'Add to cart View cart' ).nth( 2 )
			).toBeVisible();

			// Check that the cheapest appears before the priciest in the list
			const lowToHighList = await page
				.getByRole( 'listitem' )
				.getByRole( 'heading' )
				.allInnerTexts();
			const lowToHigh_index_priciest = lowToHighList.indexOf(
				`${ simpleProductName } 3`
			);
			const lowToHigh_index_cheapest = lowToHighList.indexOf(
				`${ simpleProductName } 1`
			);
			expect( lowToHigh_index_cheapest ).toBeLessThan(
				lowToHigh_index_priciest
			);
		} );
	} );
} );
