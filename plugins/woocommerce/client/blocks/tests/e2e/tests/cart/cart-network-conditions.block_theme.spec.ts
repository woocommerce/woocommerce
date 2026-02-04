/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { SIMPLE_VIRTUAL_PRODUCT_NAME } from '../checkout/constants';

/**
 * Tests for cart behavior under various network conditions.
 * These tests simulate slow networks, timeouts, and failures to ensure
 * the cart batching system handles these scenarios gracefully.
 */
test.describe( 'Cart → Network Conditions', () => {
	test.beforeEach( async ( { requestUtils } ) => {
		await requestUtils.rest( {
			method: 'PUT',
			path: 'wc/v3/settings/general/woocommerce_calc_taxes',
			data: { value: 'no' },
		} );
	} );

	test( 'should handle slow network when updating quantity', async ( {
		page,
		frontendUtils,
	} ) => {
		// Add product to cart
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( SIMPLE_VIRTUAL_PRODUCT_NAME );
		await frontendUtils.goToCart();

		// Simulate slow network (2 second delay)
		await page.route( '**/wc/store/v1/**', async ( route ) => {
			await new Promise( ( resolve ) => setTimeout( resolve, 2000 ) );
			await route.continue();
		} );

		// Update quantity
		const quantityInput = page.getByLabel(
			`Quantity of ${ SIMPLE_VIRTUAL_PRODUCT_NAME } in your cart.`
		);
		await quantityInput.fill( '3' );

		// Verify button is disabled during slow network request
		const checkoutButton = page.getByRole( 'link', {
			name: 'Proceed to Checkout',
		} );
		await expect( checkoutButton ).toBeDisabled();

		// Wait for slow request to complete (should take at least 2 seconds)
		await expect( checkoutButton ).toBeEnabled( { timeout: 5000 } );

		// Verify quantity was updated
		await expect( quantityInput ).toHaveValue( '3' );
	} );

	test( 'should handle network timeout gracefully', async ( {
		page,
		frontendUtils,
	} ) => {
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( SIMPLE_VIRTUAL_PRODUCT_NAME );
		await frontendUtils.goToCart();

		// Simulate network timeout (request never completes)
		await page.route( '**/wc/store/v1/**', async ( route ) => {
			// Never call route.continue() - simulates timeout
			await new Promise( () => {} ); // Infinite promise
		} );

		const quantityInput = page.getByLabel(
			`Quantity of ${ SIMPLE_VIRTUAL_PRODUCT_NAME } in your cart.`
		);
		const initialQuantity = await quantityInput.inputValue();

		// Try to update quantity
		await quantityInput.fill( '5' );

		// Should show optimistic update
		await expect( quantityInput ).toHaveValue( '5' );

		// Wait for timeout (usually 10-30 seconds)
		// After timeout, quantity should revert to initial value
		await page.waitForTimeout( 15000 );

		// Verify quantity reverted (if timeout handling is implemented)
		// This will fail if timeout handling is not implemented
		await expect( quantityInput ).toHaveValue( initialQuantity );
	} );

	test( 'should handle network failure and show error', async ( {
		page,
		frontendUtils,
	} ) => {
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( SIMPLE_VIRTUAL_PRODUCT_NAME );
		await frontendUtils.goToCart();

		// Simulate network failure
		await page.route( '**/wc/store/v1/**', ( route ) =>
			route.abort( 'failed' )
		);

		const quantityInput = page.getByLabel(
			`Quantity of ${ SIMPLE_VIRTUAL_PRODUCT_NAME } in your cart.`
		);
		const initialQuantity = await quantityInput.inputValue();

		// Try to update quantity
		await quantityInput.fill( '5' );

		// Wait a bit for error handling
		await page.waitForTimeout( 2000 );

		// Should show an error notice
		await expect( page.getByText( /error|failed|problem/i ) ).toBeVisible();

		// Quantity should revert to initial value
		await expect( quantityInput ).toHaveValue( initialQuantity );
	} );

	test( 'should batch operations despite network delays', async ( {
		page,
		frontendUtils,
	} ) => {
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( SIMPLE_VIRTUAL_PRODUCT_NAME );
		await frontendUtils.goToCart();

		// Simulate slow network with request counting
		let requestCount = 0;
		await page.route( '**/wc/store/v1/**', async ( route ) => {
			requestCount++;
			await new Promise( ( resolve ) => setTimeout( resolve, 500 ) );
			await route.continue();
		} );

		const quantityInput = page.getByLabel(
			`Quantity of ${ SIMPLE_VIRTUAL_PRODUCT_NAME } in your cart.`
		);

		// Make multiple rapid quantity changes
		// These should be batched into fewer requests
		await quantityInput.fill( '2' );
		await quantityInput.fill( '3' );
		await quantityInput.fill( '4' );

		// Wait for completion
		const checkoutButton = page.getByRole( 'link', {
			name: 'Proceed to Checkout',
		} );
		await expect( checkoutButton ).toBeEnabled( { timeout: 3000 } );

		// Verify final quantity
		await expect( quantityInput ).toHaveValue( '4' );

		// Verify batching occurred (should be less than 3 requests)
		expect( requestCount ).toBeLessThan( 3 );
	} );

	test( 'should handle intermittent network failures with retry', async ( {
		page,
		frontendUtils,
	} ) => {
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( SIMPLE_VIRTUAL_PRODUCT_NAME );
		await frontendUtils.goToCart();

		// First request fails, second succeeds
		let requestAttempt = 0;
		await page.route( '**/wc/store/v1/**', async ( route ) => {
			requestAttempt++;
			if ( requestAttempt === 1 ) {
				await route.abort( 'failed' );
			} else {
				await route.continue();
			}
		} );

		const quantityInput = page.getByLabel(
			`Quantity of ${ SIMPLE_VIRTUAL_PRODUCT_NAME } in your cart.`
		);

		// Update quantity
		await quantityInput.fill( '3' );

		// Wait for retry to succeed
		await page.waitForTimeout( 3000 );

		// Should eventually succeed if retry is implemented
		// This will fail if no retry mechanism exists
		await expect( quantityInput ).toHaveValue( '3' );
	} );

	test( 'should not show 400 error in minicart', async ( {
		page,
		frontendUtils,
		miniCartUtils,
	} ) => {
		await frontendUtils.goToShop();

		// Open mini cart
		await miniCartUtils.openMiniCart();

		// Add product while mini cart is open
		const shopPage = await page.context().newPage();
		await shopPage.goto( '/shop' );

		// Simulate a 400 error response
		await page.route( '**/wc/store/v1/**', ( route ) => {
			route.fulfill( {
				status: 400,
				body: JSON.stringify( {
					code: 'woocommerce_rest_cart_invalid_product',
					message: 'Invalid product',
				} ),
			} );
		} );

		await shopPage
			.getByRole( 'button', {
				name: `Add to cart: "${ SIMPLE_VIRTUAL_PRODUCT_NAME }"`,
			} )
			.click();

		// Wait a bit for error handling
		await page.waitForTimeout( 2000 );

		// Mini cart should not show error
		// This test will fail if 400 errors are shown in mini cart
		await expect(
			page.locator( '.wc-block-components-notice-banner__content' )
		).not.toBeVisible();
	} );

	test( 'should prevent double batch requests', async ( {
		page,
		frontendUtils,
	} ) => {
		await frontendUtils.goToShop();

		// Track batch requests
		const batchRequests: any[] = [];
		await page.route( '**/wc/store/v1/batch**', async ( route ) => {
			const request = route.request();
			batchRequests.push( {
				url: request.url(),
				postData: request.postData(),
				timestamp: Date.now(),
			} );
			await route.continue();
		} );

		// Click add to cart multiple times rapidly
		const addToCartButton = page
			.getByRole( 'button', {
				name: `Add to cart: "${ SIMPLE_VIRTUAL_PRODUCT_NAME }"`,
			} )
			.first();

		await addToCartButton.click();
		await addToCartButton.click();
		await addToCartButton.click();

		// Wait for requests to complete
		await page.waitForTimeout( 2000 );

		// Analyze batch timing
		// Should not see multiple batches sent at the same time
		for ( let i = 1; i < batchRequests.length; i++ ) {
			const timeDiff =
				batchRequests[ i ].timestamp - batchRequests[ i - 1 ].timestamp;

			// Batches should be at least 50ms apart (not concurrent)
			// This will fail if double batch issue exists
			expect( timeDiff ).toBeGreaterThan( 50 );
		}

		// Should not see excessive batches
		expect( batchRequests.length ).toBeLessThanOrEqual( 2 );
	} );
} );
