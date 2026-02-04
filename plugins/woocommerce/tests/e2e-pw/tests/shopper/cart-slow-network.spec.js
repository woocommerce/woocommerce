const { test, expect } = require( '@playwright/test' );
const { disableWelcomeModal } = require( '../../utils/editor' );

const SIMPLE_PHYSICAL_PRODUCT_NAME = 'Simple product';

test.describe( 'Cart - Slow Network Bugs', () => {
	test.use( { storageState: process.env.ADMINSTATE } );

	test.beforeEach( async ( { page } ) => {
		await disableWelcomeModal( { page } );
	} );

	test( 'Bug 1: batch requests delayed by 10 seconds should not cause 400 errors', async ( {
		page,
		baseURL,
	} ) => {
		// Go to shop page
		await page.goto( `${ baseURL }/shop` );

		// Add a product to cart
		await page.locator( '.add_to_cart_button' ).first().click();
		await page.waitForTimeout( 2000 );

		// Go to cart
		await page.goto( `${ baseURL }/cart` );

		// Set up request interception to delay batch requests by 10 seconds
		await page.route( '**/wc/store/v1/batch', async ( route ) => {
			console.log( 'Intercepted batch request - delaying by 10 seconds' );
			await new Promise( ( resolve ) => setTimeout( resolve, 10000 ) );
			await route.continue();
		} );

		// Find quantity input
		const quantityInput = page.locator( '.qty' ).first();

		// Update quantity - this should trigger a batch request
		await quantityInput.fill( '3' );

		// Trigger the update (usually happens on blur)
		await quantityInput.press( 'Tab' );

		console.log( 'Waiting for slow request to complete (10+ seconds)...' );

		// Wait for the delayed request to complete
		await page.waitForTimeout( 12000 );

		// Check for 400 errors
		// This test will FAIL if a 400 error occurs
		const errorNotice = page.locator( '.woocommerce-error' );
		await expect( errorNotice ).not.toBeVisible();

		// Verify quantity is correct
		await expect( quantityInput ).toHaveValue( '3' );

		console.log( 'Test completed - checking for 400 errors' );
	} );

	test( 'Bug 2: 400 error in minicart without notice', async ( {
		page,
		baseURL,
	} ) => {
		// Add multiple products to cart first
		await page.goto( `${ baseURL }/shop` );

		// Add first product
		await page.locator( '.add_to_cart_button' ).first().click();
		await page.waitForTimeout( 1000 );

		// Add second product
		await page.locator( '.add_to_cart_button' ).nth( 1 ).click();
		await page.waitForTimeout( 1000 );

		// Track if we get a 400 response
		let got400Error = false;
		page.on( 'response', ( response ) => {
			if (
				response.url().includes( '/batch' ) &&
				response.status() === 400
			) {
				console.log( 'Got 400 error from batch endpoint' );
				got400Error = true;
			}
		} );

		// Open mini cart (if available)
		const miniCartButton = page.locator( '.wc-block-mini-cart__button' );
		if ( await miniCartButton.isVisible() ) {
			await miniCartButton.click();
			await page.waitForTimeout( 1000 );

			// Perform multiple operations
			// Try to update quantities
			const increaseButton = page
				.locator( '[aria-label*="Increase"]' )
				.first();
			if ( await increaseButton.isVisible() ) {
				await increaseButton.click();
			}

			const decreaseButton = page
				.locator( '[aria-label*="Decrease"]' )
				.first();
			if ( await decreaseButton.isVisible() ) {
				await decreaseButton.click();
			}

			// Wait for any batch requests
			await page.waitForTimeout( 3000 );

			// If we got a 400 error, there should be a notice
			if ( got400Error ) {
				const notice = page.locator(
					'.wc-block-components-notice-banner'
				);
				// This assertion will FAIL if 400 errors don't show notices
				await expect( notice ).toBeVisible();
			}
		} else {
			console.log( 'Mini cart not available, skipping mini cart test' );
		}
	} );
} );
