/**
 * External dependencies
 */
import {
	closeChoosePatternModal,
	getCanvas,
	goToPageEditor,
	insertBlock,
	publishPage,
} from '@woocommerce/e2e-utils-playwright';

/**
 * Internal dependencies
 */
import { ADMIN_STATE_PATH } from '../../playwright.config';
import { WC_API_PATH } from '../../utils/api-client';
import { expect, tags, test as baseTest } from '../../fixtures/fixtures';
import { fillPageTitle } from '../../utils/editor';
import { getInstalledWordPressVersion } from '../../utils/wordpress';

const simpleProductName = 'Simplest Product';
const singleProductPrice = '555.00';

// All WooCommerce blocks except:
// - default cart and checkout blocks, mini-cart
// - Product Gallery (Beta) - it's not intended to be used in posts

/**
 * The key of the object is the block name
 * The value is custom function to check the block
 * If the value is null, there is a default check for the block
 */
const blocks = {
	'All Reviews': null,
	'Best Sellers': null,
	'Cross-Sells': async ( canvas ) => {
		await canvas
			.getByRole( 'listitem' )
			.filter( { hasText: /^Simplest Product$/ } )
			.click();
		await expect(
			canvas
				.getByRole( 'document', {
					name: `Block: Cross-Sells`,
					exact: true,
				} )
				.first()
		).toBeVisible();
	},
	'Customer account': null,
	'Featured Category': async ( canvas ) => {
		await canvas
			.getByRole( 'listitem' )
			.filter( { hasText: 'simple category' } )
			.click();

		await canvas.getByRole( 'button', { name: 'Done' } ).click();

		await expect(
			canvas.getByRole( 'document', { name: `Block: Featured Category` } )
		).toBeVisible();
	},
	'Featured Product': async ( canvas ) => {
		await canvas
			.getByRole( 'listitem' )
			.filter( { hasText: /^Simplest Product$/ } )
			.click();

		await canvas.getByRole( 'button', { name: 'Done' } ).click();

		await expect(
			canvas.getByRole( 'document', { name: `Block: Featured Product` } )
		).toBeVisible();
	},
	'Featured Products': null,
	'Hand-Picked Products': null,
	'New Arrivals': null,
	'On Sale Products': null,
	'Product Categories List': null,
	'Product Collection': null,
	'Product Search': null,
	'Reviews by Category': async ( canvas ) => {
		const block = canvas.getByRole( 'document', {
			name: `Block: Reviews by Category`,
		} );

		await block.filter( { hasText: 'simple category' } ).click();
		await canvas.getByRole( 'button', { name: 'Done' } ).click();

		await expect( block ).toBeVisible();
	},
	'Reviews by Product': async ( canvas ) => {
		await canvas
			.locator( '.wc-block-reviews-by-product' )
			.getByLabel( simpleProductName )
			.click();
		await canvas
			.locator( '.wc-block-reviews-by-product' )
			.getByRole( 'button', {
				name: 'Done',
				exact: true,
			} )
			.click();

		// verify added blocks into page
		await expect(
			canvas.getByRole( 'document', {
				name: `Block: Reviews by Product`,
				exact: true,
			} )
		).toBeVisible();
	},
	'Single Product': async ( canvas ) => {
		await canvas
			.getByRole( 'listitem' )
			.filter( { hasText: /^Simplest Product$/ } )
			.click();

		await canvas.getByRole( 'button', { name: 'Done' } ).click();

		await expect(
			canvas.getByRole( 'document', { name: `Block: Single Product` } )
		).toBeVisible();
	},
	'Store Notices': null,
	'Top Rated Products': null,
	Upsells: async ( canvas ) => {
		await canvas
			.getByRole( 'listitem' )
			.filter( { hasText: /^Simplest Product$/ } )
			.click();

		await expect(
			canvas.getByRole( 'document', { name: `Block: Upsells` } )
		).toBeVisible();
	},
};

let productId, shippingZoneId, productTagId, attributeId, productCategoryId;

const test = baseTest.extend( {
	storageState: ADMIN_STATE_PATH,
	testPageTitlePrefix: 'Woocommerce Blocks',
} );

test.describe(
	'Add WooCommerce Blocks Into Page',
	{
		tag: [ tags.GUTENBERG, tags.SKIP_ON_EXTERNAL_ENV ],
	},
	() => {
		test.beforeAll( async ( { restApi } ) => {
			// add product attribute
			await restApi
				.post( `${ WC_API_PATH }/products/attributes`, {
					name: 'testattribute',
					has_archives: true,
				} )
				.then( ( response ) => {
					attributeId = response.data.id;
				} );
			// add product attribute term
			await restApi.post(
				`${ WC_API_PATH }/products/attributes/${ attributeId }/terms`,
				{
					name: 'attributeterm',
				}
			);
			// add product categories
			await restApi
				.post( `${ WC_API_PATH }/products/categories`, {
					name: 'simple category',
				} )
				.then( ( response ) => {
					productCategoryId = response.data.id;
				} );
			// add product tags
			await restApi
				.post( `${ WC_API_PATH }/products/tags`, {
					name: 'simpletag',
				} )
				.then( ( response ) => {
					productTagId = response.data.id;
				} );
			// add product
			await restApi
				.post( `${ WC_API_PATH }/products`, {
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
			await restApi
				.post( `${ WC_API_PATH }/shipping/zones`, {
					name: 'Shipping Zone',
				} )
				.then( ( response ) => {
					shippingZoneId = response.data.id;
				} );
			await restApi.post(
				`${ WC_API_PATH }/shipping/zones/${ shippingZoneId }/methods`,
				{
					method_id: 'free_shipping',
				}
			);
		} );

		test.afterAll( async ( { restApi } ) => {
			await restApi.delete( `${ WC_API_PATH }/products/${ productId }`, {
				force: true,
			} );
			await restApi.post( `${ WC_API_PATH }/products/tags/batch`, {
				delete: [ productTagId ],
			} );
			await restApi.post( `${ WC_API_PATH }/products/attributes/batch`, {
				delete: [ attributeId ],
			} );
			await restApi.post( `${ WC_API_PATH }/products/categories/batch`, {
				delete: [ productCategoryId ],
			} );
			await restApi.delete(
				`${ WC_API_PATH }/shipping/zones/${ shippingZoneId }`,
				{
					force: true,
				}
			);
		} );

		test( `can insert all WooCommerce blocks into page`, async ( {
			page,
			testPage,
		} ) => {
			await goToPageEditor( { page } );

			await closeChoosePatternModal( { page } );

			await fillPageTitle( page, testPage.title );

			const wordPressVersion = await getInstalledWordPressVersion();

			for ( const [ blockName, check ] of Object.entries( blocks ) ) {
				await test.step( `Insert ${ blockName } block`, async () => {
					await insertBlock( page, blockName, wordPressVersion );

					const canvas = await getCanvas( page );

					// eslint-disable-next-line playwright/no-conditional-in-test
					if ( check ) {
						await check( canvas );
						return;
					}

					// verify added blocks into page
					await expect(
						canvas
							.getByRole( 'document', {
								name: `Block: ${ blockName }`,
								exact: true,
							} )
							.first()
					).toBeVisible();

					// Add a new empty block to insert the next block into.
					await page
						.getByLabel( 'Block tools' )
						.getByLabel( 'Options' )
						.click();
					await page.getByText( 'Add after' ).click();
				} );
			}

			await publishPage( page, testPage.title );

			// check all blocks inside the page after publishing
			// except the product price due to invisibility and false-positive
			const canvas = await getCanvas( page );
			for ( const [ blockName ] of Object.entries( blocks ) ) {
				// verify added blocks into page
				await expect(
					canvas
						.getByRole( 'document', {
							name: `Block: ${ blockName }`,
							exact: true,
						} )
						.first()
				).toBeVisible();
			}
		} );
	}
);
