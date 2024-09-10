/**
 * External dependencies
 */
import { test as base, expect } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import ProductCollectionPage from './product-collection.page';

const test = base.extend< { pageObject: ProductCollectionPage } >( {
	pageObject: async ( { page, admin, editor }, use ) => {
		const pageObject = new ProductCollectionPage( {
			page,
			admin,
			editor,
		} );
		await use( pageObject );
	},
} );

test.describe( 'Product Collection - extensibility JS events', () => {
	test( 'emits wc-blocks_product_list_rendered event on init and on page change', async ( {
		pageObject,
		page,
	} ) => {
		await pageObject.createNewPostAndInsertBlock();

		await page.addInitScript( () => {
			let eventFired = 0;
			window.document.addEventListener(
				'wc-blocks_product_list_rendered',
				( e ) => {
					const { collection } = e.detail;
					window.eventPayload = collection;
					window.eventFired = ++eventFired;
				}
			);
		} );

		await pageObject.publishAndGoToFrontend();

		await expect
			.poll( async () => await page.evaluate( 'window.eventPayload' ) )
			.toBe( undefined );
		await expect
			.poll( async () => await page.evaluate( 'window.eventFired' ) )
			.toBe( 1 );

		await page.getByRole( 'link', { name: 'Next Page' } ).click();

		await expect
			.poll( async () => await page.evaluate( 'window.eventFired' ) )
			.toBe( 2 );
	} );

	test( 'emits one wc-blocks_product_list_rendered event per block', async ( {
		pageObject,
		page,
	} ) => {
		// Adding three blocks in total
		await pageObject.createNewPostAndInsertBlock();
		await pageObject.insertProductCollection();
		await pageObject.chooseCollectionInPost();
		await pageObject.insertProductCollection();
		await pageObject.chooseCollectionInPost();

		await page.addInitScript( () => {
			let eventFired = 0;
			window.document.addEventListener(
				'wc-blocks_product_list_rendered',
				() => {
					window.eventFired = ++eventFired;
				}
			);
		} );

		await pageObject.publishAndGoToFrontend();

		await expect
			.poll( async () => await page.evaluate( 'window.eventFired' ) )
			.toBe( 3 );
	} );

	test.describe( 'wc-blocks_viewed_product is emitted', () => {
		let promise: Promise< { productId?: number; collection?: string } >;

		test.beforeEach( async ( { page, pageObject } ) => {
			await pageObject.createNewPostAndInsertBlock( 'featured' );

			promise = new Promise( ( resolve ) => {
				void page.exposeFunction( 'resolvePayload', resolve );
				void page.addInitScript( () => {
					window.document.addEventListener(
						'wc-blocks_viewed_product',
						( e ) => {
							window.resolvePayload( e.detail );
						}
					);
				} );
			} );

			await pageObject.publishAndGoToFrontend();
		} );

		test( 'when Product Image is clicked', async ( { page } ) => {
			await page
				.locator( '[data-block-name="woocommerce/product-image"]' )
				.nth( 0 )
				.click();

			const { collection, productId } = await promise;
			expect( collection ).toEqual(
				'woocommerce/product-collection/featured'
			);
			expect( productId ).toEqual( expect.any( Number ) );
		} );

		test( 'when Product Title is clicked', async ( { page } ) => {
			await page.locator( '.wp-block-post-title' ).nth( 0 ).click();

			const { collection, productId } = await promise;
			expect( collection ).toEqual(
				'woocommerce/product-collection/featured'
			);
			expect( productId ).toEqual( expect.any( Number ) );
		} );

		test( 'when Add to Cart Anchor is clicked', async ( { page } ) => {
			await page.getByLabel( 'Select options for “V-Neck T-' ).click();

			const { collection, productId } = await promise;
			expect( collection ).toEqual(
				'woocommerce/product-collection/featured'
			);
			expect( productId ).toEqual( expect.any( Number ) );
		} );
	} );
} );
