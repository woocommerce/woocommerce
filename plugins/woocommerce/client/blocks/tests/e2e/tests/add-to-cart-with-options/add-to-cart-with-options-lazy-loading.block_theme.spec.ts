/**
 * External dependencies
 */
import { test as base, expect } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import AddToCartWithOptionsPage from './add-to-cart-with-options.page';

const test = base.extend< {
	pageObject: AddToCartWithOptionsPage;
} >( {
	pageObject: async ( { page, admin, editor }, use ) => {
		const pageObject = new AddToCartWithOptionsPage( {
			page,
			admin,
			editor,
		} );
		await use( pageObject );
	},
} );

test.describe( 'Add to Cart + Options Block - Lazy Loading Variations', () => {
	// Activate the plugin that lowers the threshold to 3, so Hoodie (~6 variations)
	// will trigger lazy loading mode.
	test.beforeAll( async ( { requestUtils } ) => {
		await requestUtils.activatePlugin(
			'woocommerce-blocks-test-lazy-load-variations'
		);
	} );

	test.afterAll( async ( { requestUtils } ) => {
		await requestUtils.deactivatePlugin(
			'woocommerce-blocks-test-lazy-load-variations'
		);
		await requestUtils.deleteAllTemplates( 'wp_template' );
	} );

	test( 'fetches variation data via AJAX and updates UI when threshold is exceeded', async ( {
		page,
		pageObject,
		editor,
	} ) => {
		// Track requests to the Store API products endpoint.
		const variationRequests: string[] = [];
		page.on( 'request', ( request ) => {
			const url = request.url();
			// Match requests like /wc/store/v1/products/123 (variation fetches)
			if ( /\/wc\/store\/v1\/products\/\d+$/.test( url ) ) {
				variationRequests.push( url );
			}
		} );

		await pageObject.updateSingleProductTemplate();

		await editor.saveSiteEditorEntities( {
			isOnlyCurrentEntityDirty: true,
		} );

		await page.goto( '/product/hoodie/' );

		// Before selecting a variation, no AJAX requests should have been made.
		expect( variationRequests.length ).toBe( 0 );

		// Select a variation.
		const colorBlueOption = page.locator( 'label:has-text("Blue")' );
		const logoNoOption = page.locator( 'label:has-text("No")' );

		await colorBlueOption.click();
		await logoNoOption.click();

		// Wait for the variation data to be fetched and UI to update.
		await expect( async () => {
			// At least one AJAX request should have been made to fetch variation data.
			expect( variationRequests.length ).toBeGreaterThan( 0 );
		} ).toPass( { timeout: 5000 } );

		// Verify the UI updates correctly with the fetched data.
		const productPrice = page
			.locator( '.wp-block-woocommerce-product-price' )
			.first();
		// Use regex to handle both regular and sale price formats.
		await expect( productPrice ).toHaveText( /\$45\.00/ );

		// Verify add to cart works with lazy-loaded variation.
		const addToCartButton = page
			.locator( '.wp-block-add-to-cart-with-options' )
			.getByRole( 'button', { name: 'Add to cart' } );

		await addToCartButton.click();

		// Verify success.
		await expect( page.getByText( '1 in cart' ) ).toBeVisible();
	} );
} );