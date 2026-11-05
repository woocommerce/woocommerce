/**
 * External dependencies
 */
import { WC_API_PATH } from '@woocommerce/e2e-utils-playwright';

/**
 * Internal dependencies
 */
import { test, expect, tags } from '../../fixtures/fixtures';
import { getFakeCategory, getFakeProduct } from '../../utils/data';

test.describe(
	'Search, browse by categories and sort items in the shop',
	{ tag: [ tags.PAYMENTS, tags.SERVICES ] },
	() => {
		// One dedicated category holds all of this spec's products, so browsing
		// and sorting happen on the category archive — which only ever lists this
		// spec's own products. That keeps the spec immune to the store-wide shop
		// listing that other parallel workers pollute by creating/deleting
		// products (and to its 16-per-page pagination).
		let category;
		let products = [];

		test.beforeAll( async ( { restApi } ) => {
			await restApi
				.post(
					`${ WC_API_PATH }/products/categories`,
					getFakeCategory( { extraRandomTerm: true } )
				)
				.then( ( response ) => {
					category = response.data;
				} )
				.catch( ( error: { response: unknown } ) => {
					console.error( error.response );
				} );

			await restApi
				.post( `${ WC_API_PATH }/products/batch`, {
					create: [
						{
							...getFakeProduct( { regular_price: '979.99' } ),
							categories: [ { id: category.id } ],
						},
						{
							...getFakeProduct( { regular_price: '989.99' } ),
							categories: [ { id: category.id } ],
						},
						{
							...getFakeProduct( { regular_price: '999.99' } ),
							categories: [ { id: category.id } ],
						},
					],
				} )
				.then( ( response ) => {
					products = response.data.create;
				} )
				.catch( ( error: { response: unknown } ) => {
					console.error( error.response );
				} );
		} );

		test.afterAll( async ( { restApi } ) => {
			await restApi.post( `${ WC_API_PATH }/products/batch`, {
				delete: products.map( ( product ) => product.id ),
			} );
			await restApi.post( `${ WC_API_PATH }/products/categories/batch`, {
				delete: [ category.id ],
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
			await test.step( 'Open a product and browse to its category via the breadcrumb', async () => {
				await page.goto( products[ 1 ].permalink );
				await page
					.getByLabel( 'Breadcrumb' )
					.getByRole( 'link', {
						name: category.name,
						exact: true,
					} )
					.click();
			} );

			await test.step( 'Ensure the category page contains all the relevant products', async () => {
				await expect(
					page.getByRole( 'heading', { name: category.name } )
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
			const categoryUrl = `product-category/${ category.slug }/`;

			// Reads the displayed price of every product on the (isolated)
			// category archive, in DOM order, as plain numbers.
			const getDisplayedPrices = async () => {
				const priceTexts = await page
					.locator( 'li.product .woocommerce-Price-amount' )
					.allInnerTexts();
				return priceTexts.map( ( text ) =>
					parseFloat( text.replace( /[^0-9.]/g, '' ) )
				);
			};

			await test.step( 'Sort by price high to low', async () => {
				await page.goto( categoryUrl );
				await page
					.getByLabel( 'Shop order' )
					.selectOption( 'price-desc' );
				await page.waitForURL( /.*?orderby=price-desc.*/ );
				await expect( page.locator( 'li.product' ) ).toHaveCount(
					products.length
				);

				const prices = await getDisplayedPrices();
				expect( prices ).toEqual(
					[ ...prices ].sort( ( a, b ) => b - a )
				);
			} );

			await test.step( 'Sort by price low to high', async () => {
				await page.goto( categoryUrl );
				await page.getByLabel( 'Shop order' ).selectOption( 'price' );
				await page.waitForURL( /.*?orderby=price(?:&|$).*/ );
				await expect( page.locator( 'li.product' ) ).toHaveCount(
					products.length
				);

				const prices = await getDisplayedPrices();
				expect( prices ).toEqual(
					[ ...prices ].sort( ( a, b ) => a - b )
				);
			} );
		} );
	}
);
