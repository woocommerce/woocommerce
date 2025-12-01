/**
 * External dependencies
 */
import { WC_API_PATH } from '@woocommerce/e2e-utils-playwright';

/**
 * Internal dependencies
 */
import { test } from '../../../fixtures/block-editor-fixtures';
import { tags, expect } from '../../../fixtures/fixtures';
import { clickOnTab } from '../../../utils/simple-products';
import { getFakeProduct } from '../../../utils/data';
import { skipTestsForDeprecatedFeature } from './helpers/skip-tests';

skipTestsForDeprecatedFeature();

const NEW_EDITOR_ADD_PRODUCT_URL =
	'wp-admin/admin.php?page=wc-admin&path=%2Fadd-product';

const isTrackingSupposedToBeEnabled = !! process.env.ENABLE_TRACKING;

const productData = {
	name: `Grouped product Name ${ new Date().getTime().toString() }`,
	summary: 'This is a product summary',
};

const groupedProducts = [];

test.describe( 'General tab', { tag: tags.GUTENBERG }, () => {
	test.describe( 'Grouped product', () => {
		test.beforeAll( async ( { restApi } ) => {
			for ( let i = 0; i < 2; i++ ) {
				await restApi
					.post( `${ WC_API_PATH }/products`, getFakeProduct() )
					.then( ( response ) =>
						groupedProducts.push( response.data )
					);
			}
		} );

		test.afterAll( async ( { restApi } ) => {
			for ( const p of groupedProducts ) {
				await restApi
					.delete( `${ WC_API_PATH }/products/${ p.id }`, {
						force: true,
					} )
					.catch( ( err ) => {
						console.log( err );
					} );
			}
		} );
		test.skip(
			isTrackingSupposedToBeEnabled,
			'The block product editor is not being tested'
		);

		test( 'can create a grouped product', async ( { page } ) => {
			await page.goto( NEW_EDITOR_ADD_PRODUCT_URL );
			await clickOnTab( 'General', page );
			const waitForProductResponse = page.waitForResponse(
				( response ) =>
					response.url().includes( '/wp-json/wc/v3/products/' ) &&
					response.status() === 200
			);
			await page
				.getByPlaceholder( 'e.g. 12 oz Coffee Mug' )
				.fill( productData.name );
			await page
				.locator(
					'[data-template-block-id="basic-details"] .components-summary-control'
				)
				.last()
				.fill( productData.summary );

			await page
				.getByRole( 'button', {
					name: 'Change product type',
				} )
				.click();

			await page
				.locator( '.components-dropdown__content' )
				.getByText( 'Grouped product' )
				.click();

			await expect(
				page.getByLabel( 'Dismiss this notice' )
			).toContainText( 'Product type changed.' );

			await waitForProductResponse;

			await page
				.locator( '[data-title="Product section"]' )
				.getByText( 'Add products' )
				.click();

			await page
				.getByRole( 'heading', {
					name: 'Add products to this group',
				} )
				.isVisible();

			for ( const product of groupedProducts ) {
				await page
					.locator(
						'.woocommerce-add-products-modal__form-group-content'
					)
					.getByPlaceholder( 'Search for products' )
					.fill( product.name );

				// await page.pause();
				await page.getByText( product.name ).click();
			}

			await page
				.locator( '.woocommerce-add-products-modal__actions' )
				.getByRole( 'button', {
					name: 'Add',
				} )
				.click();

			await page
				.locator( '.woocommerce-product-header__actions' )
				.getByRole( 'button', {
					name: 'Publish',
				} )
				.click();

			const element = page.locator( 'div.components-snackbar__content' );
			const textContent = await element.innerText();

			await expect( textContent ).toMatch( 'Product type changed.' );

			await page.waitForResponse(
				( response ) =>
					response.url().includes( '/wp-json/wc/v3/products/' ) &&
					response.status() === 200
			);

			const title = page.locator( '.woocommerce-product-header__title' );

			// Save product ID
			const productIdRegex = /product%2F(\d+)/;
			const url = page.url();
			const productIdMatch = productIdRegex.exec( url );
			const productId = productIdMatch ? productIdMatch[ 1 ] : null;

			await expect( productId ).toBeDefined();
			await expect( title ).toHaveText( productData.name );

			await page.goto( `?post_type=product&p=${ productId }` );

			await expect(
				page.getByRole( 'heading', { name: productData.name } )
			).toBeVisible();

			for ( const product of groupedProducts ) {
				await expect(
					page.getByRole( 'link', { name: product.name } ).first()
				).toBeVisible();
			}
		} );
	} );
} );
