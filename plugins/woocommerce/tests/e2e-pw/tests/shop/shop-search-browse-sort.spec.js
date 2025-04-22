/**
 * Internal dependencies
 */
import { test, expect, tags } from '../../fixtures/fixtures';
import { getFakeCategory, getFakeProduct } from '../../utils/data';
import { WC_API_PATH } from '../../utils/api-client';

test.describe(
	'Search, browse by categories and sort items in the shop',
	{ tag: [ tags.PAYMENTS, tags.SERVICES ] },
	() => {
		let categories = [];
		let products = [];

		test.beforeAll( async ( { restApi } ) => {
			await restApi
				.post( `${ WC_API_PATH }/products/categories/batch`, {
					create: [
						getFakeCategory( { extraRandomTerm: true } ),
						getFakeCategory( { extraRandomTerm: true } ),
						getFakeCategory( { extraRandomTerm: true } ),
					],
				} )
				.then( ( response ) => {
					categories = response.data.create;

					if ( categories.map( ( c ) => c.id ).includes( 0 ) ) {
						console.log( JSON.stringify( response.data ) );
					}
				} )
				.catch( ( error ) => {
					console.error( error.response );
				} );

			await restApi
				.post( `${ WC_API_PATH }/products/batch`, {
					create: [
						{
							...getFakeProduct( { regular_price: '979.99' } ),
							categories: [ { id: categories[ 0 ].id } ],
						},
						{
							...getFakeProduct( { regular_price: '989.99' } ),
							categories: [ { id: categories[ 1 ].id } ],
						},
						{
							...getFakeProduct( { regular_price: '999.99' } ),
							categories: [ { id: categories[ 2 ].id } ],
						},
					],
				} )
				.then( ( response ) => {
					products = response.data.create;
				} )
				.catch( ( error ) => {
					console.error( error.response );
				} );
		} );

		test.afterAll( async ( { restApi } ) => {
			await restApi.post( `${ WC_API_PATH }/products/batch`, {
				delete: [
					products[ 0 ].id,
					products[ 1 ].id,
					products[ 2 ].id,
				],
			} );
			await restApi.post( `${ WC_API_PATH }/products/categories/batch`, {
				delete: [
					categories[ 0 ].id,
					categories[ 1 ].id,
					categories[ 2 ].id,
				],
			} );
		} );

		// default theme doesn't have a search box, but can simulate a search by visiting the search URL
		test( 'should let user search the store', async ( { page } ) => {
			await test.step( 'Go to the shop and perform the search', async () => {
				await page.goto( `shop/?s=${ products[ 0 ].name }` );

				await expect(
					page.getByRole( 'heading', {
						name: `${ products[ 0 ].name }`,
						level: 1,
					} )
				).toBeVisible();
				await expect( page.getByLabel( 'Breadcrumb' ) ).toContainText(
					`${ products[ 0 ].name }`
				);
			} );
		} );

		test( 'should let user browse products by categories', async ( {
			page,
		} ) => {
			await test.step( 'Go to the shop and browse by the category', async () => {
				await page.goto( 'shop/' );
				await page.locator( `text=${ products[ 1 ].name }` ).click();
				await page
					.getByLabel( 'Breadcrumb' )
					.getByRole( 'link', {
						name: categories[ 1 ].name,
						exact: true,
					} )
					.click();
			} );

			await test.step( 'Ensure the category page contains all the relevant products', async () => {
				await expect(
					page.getByRole( 'heading', { name: categories[ 1 ].name } )
				).toBeVisible();
				await expect(
					page.getByRole( 'heading', {
						name: products[ 1 ].name,
					} )
				).toBeVisible();
				await page.locator( `text=${ products[ 1 ].name }` ).click();
				await expect(
					page.getByRole( 'heading', {
						name: products[ 1 ].name,
						level: 1,
					} )
				).toBeVisible();
			} );
		} );

		test( 'should let user sort the products in the shop', async ( {
			page,
		} ) => {
			await test.step( 'Go to the shop and sort by price high to low', async () => {
				await page.goto( 'shop/' );
				await expect(
					page.getByLabel(
						new RegExp(
							`Add to cart: ["|“]${ products[ 0 ].name }["|”]`
						)
					)
				).toBeVisible();

				// sort by price high to low
				await page
					.getByLabel( 'Shop order' )
					.selectOption( 'price-desc' );
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
					`${ products[ 2 ].name }`
				);
				const highToLow_index_cheapest = highToLowList.indexOf(
					`${ products[ 0 ].name }`
				);
				expect( highToLow_index_priciest ).toBeLessThan(
					highToLow_index_cheapest
				);
			} );

			await test.step( 'Go to the shop and sort by price low to high', async () => {
				await page.goto( 'shop/' );
				await expect(
					page.getByLabel(
						new RegExp(
							`Add to cart: ["|“]${ products[ 0 ].name }["|”]`
						)
					)
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
					`${ products[ 2 ].name }`
				);
				const lowToHigh_index_cheapest = lowToHighList.indexOf(
					`${ products[ 0 ].name }`
				);
				expect( lowToHigh_index_cheapest ).toBeLessThan(
					lowToHigh_index_priciest
				);
			} );
		} );
	}
);
