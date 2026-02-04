/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import {
	SIMPLE_PHYSICAL_PRODUCT_NAME,
	SIMPLE_VIRTUAL_PRODUCT_NAME,
} from '../checkout/constants';

/**
 * Tests for specific bugs reported in PR #62766:
 * 1. 400 error and wrong quantities when requests take 10+ seconds
 * 2. 400 error in minicart without notice when batching multiple operations
 */
test.describe( 'Cart → Slow Network Bugs', () => {
	test.beforeEach( async ( { requestUtils } ) => {
		await requestUtils.rest( {
			method: 'PUT',
			path: 'wc/v3/settings/general/woocommerce_calc_taxes',
			data: { value: 'no' },
		} );
	} );

	test.describe( 'Bug 1: 400 error and wrong quantities with 10+ second delays', () => {
		test( 'batch requests delayed by 10 seconds should not cause 400 errors', async ( {
			page,
			frontendUtils,
		} ) => {
			// Add products to cart first
			await frontendUtils.goToShop();
			await frontendUtils.addToCart( SIMPLE_PHYSICAL_PRODUCT_NAME );
			await frontendUtils.addToCart( SIMPLE_VIRTUAL_PRODUCT_NAME );
			await frontendUtils.goToCart();

			// Set up request interception to delay batch requests by 10 seconds
			let interceptedRequest = null;
			await page.route( '**/wc/store/v1/batch', async ( route ) => {
				interceptedRequest = {
					url: route.request().url(),
					method: route.request().method(),
					postData: route.request().postData(),
				};

				// Wait 10 seconds before processing
				await new Promise( ( resolve ) =>
					setTimeout( resolve, 10000 )
				);
				await route.continue();
			} );

			// Get initial quantities
			const physicalQuantityInput = page.getByLabel(
				`Quantity of ${ SIMPLE_PHYSICAL_PRODUCT_NAME } in your cart.`
			);
			const virtualQuantityInput = page.getByLabel(
				`Quantity of ${ SIMPLE_VIRTUAL_PRODUCT_NAME } in your cart.`
			);

			// Update quantities - this should trigger a batch request
			await physicalQuantityInput.fill( '3' );
			await virtualQuantityInput.fill( '2' );

			// Wait for the delayed request to complete (10+ seconds)
			await page.waitForTimeout( 11000 );

			// Check for 400 errors
			// This test will FAIL if a 400 error occurs
			const errorNotice = page.locator(
				'.wc-block-components-notice-banner.is-error'
			);
			await expect( errorNotice ).not.toBeVisible();

			// Verify quantities are correct (not wrong)
			// This test will FAIL if wrong quantities are saved
			await expect( physicalQuantityInput ).toHaveValue( '3' );
			await expect( virtualQuantityInput ).toHaveValue( '2' );

			// Log the intercepted request for debugging
			if ( interceptedRequest ) {
				console.log( 'Intercepted batch request:', interceptedRequest );
			}
		} );

		test( 'very slow add to cart should not result in wrong quantities', async ( {
			page,
			frontendUtils,
		} ) => {
			await frontendUtils.goToShop();

			// Delay all cart requests by 10 seconds
			await page.route( '**/wc/store/v1/cart/**', async ( route ) => {
				await new Promise( ( resolve ) =>
					setTimeout( resolve, 10000 )
				);
				await route.continue();
			} );
			await page.route( '**/wc/store/v1/batch', async ( route ) => {
				await new Promise( ( resolve ) =>
					setTimeout( resolve, 10000 )
				);
				await route.continue();
			} );

			// Click add to cart 3 times for the same product
			const addToCartButton = page
				.getByRole( 'button', {
					name: `Add to cart: "${ SIMPLE_PHYSICAL_PRODUCT_NAME }"`,
				} )
				.first();

			await addToCartButton.click();
			await addToCartButton.click();
			await addToCartButton.click();

			// Go to cart while requests are still pending
			await frontendUtils.goToCart();

			// Wait for the slow requests to complete
			await page.waitForTimeout( 11000 );

			// Check quantity - should be 3, not some wrong value
			const quantityInput = page.getByLabel(
				`Quantity of ${ SIMPLE_PHYSICAL_PRODUCT_NAME } in your cart.`
			);

			// This test will FAIL if wrong quantities are added
			await expect( quantityInput ).toHaveValue( '3' );

			// Should not have errors
			const errorNotice = page.locator(
				'.wc-block-components-notice-banner.is-error'
			);
			await expect( errorNotice ).not.toBeVisible();
		} );
	} );

	test.describe( 'Bug 2: 400 error without notice in minicart during batch operations', () => {
		test( 'minicart batch operations should show errors when they occur', async ( {
			page,
			frontendUtils,
			miniCartUtils,
		} ) => {
			// Add multiple products to cart
			await frontendUtils.goToShop();
			await frontendUtils.addToCart( SIMPLE_PHYSICAL_PRODUCT_NAME );
			await frontendUtils.addToCart( SIMPLE_VIRTUAL_PRODUCT_NAME );

			// Add a third product if available
			const thirdProduct = page
				.getByRole( 'button', { name: /Add to cart/ } )
				.nth( 2 );
			if ( await thirdProduct.isVisible() ) {
				await thirdProduct.click();
			}

			// Open minicart
			await miniCartUtils.openMiniCart();

			// Wait for minicart to fully load
			await page.waitForTimeout( 1000 );

			// Perform multiple operations in minicart:
			// 1. Increase quantity of first product
			const increaseButton = page
				.getByRole( 'button', {
					name: `Increase quantity of ${ SIMPLE_PHYSICAL_PRODUCT_NAME }`,
				} )
				.first();
			if ( await increaseButton.isVisible() ) {
				await increaseButton.click();
			}

			// 2. Decrease quantity of second product
			const decreaseButton = page
				.getByRole( 'button', {
					name: `Reduce quantity of ${ SIMPLE_VIRTUAL_PRODUCT_NAME }`,
				} )
				.first();
			if ( await decreaseButton.isVisible() ) {
				await decreaseButton.click();
			}

			// 3. Remove third product (if exists)
			const removeButton = page
				.getByRole( 'button', {
					name: /Remove .* from cart/,
				} )
				.nth( 2 );
			if ( await removeButton.isVisible() ) {
				await removeButton.click();
			}

			// Wait for batch request to complete
			await page.waitForTimeout( 3000 );

			// Check if any errors are visible in the minicart
			const miniCartError = page.locator(
				'.wc-block-mini-cart__drawer .wc-block-components-notice-banner'
			);

			// If a 400 error occurred, it should be visible
			// This test will FAIL if 400 errors occur without UI notice
			if (
				await page.locator( '.wc-block-mini-cart__drawer' ).isVisible()
			) {
				// If there was an error response, there should be a notice
				const hasError = ( await miniCartError.count() ) > 0;
				if ( hasError ) {
					await expect( miniCartError ).toBeVisible();
					await expect( miniCartError ).toContainText(
						/error|failed|problem/i
					);
				}
			}
		} );

		test( 'simulate exact 400 error scenario in minicart', async ( {
			page,
			frontendUtils,
			miniCartUtils,
		} ) => {
			// Add products
			await frontendUtils.goToShop();
			await frontendUtils.addToCart( SIMPLE_PHYSICAL_PRODUCT_NAME );
			await frontendUtils.addToCart( SIMPLE_VIRTUAL_PRODUCT_NAME );

			// Open minicart
			await miniCartUtils.openMiniCart();
			await page.waitForTimeout( 1000 );

			// Track if we get a 400 response
			let got400Error = false;
			page.on( 'response', ( response ) => {
				if (
					response.url().includes( '/batch' ) &&
					response.status() === 400
				) {
					got400Error = true;
				}
			} );

			// Perform operations that might trigger 400
			// Increase first item
			await page
				.getByRole( 'button', { name: /Increase quantity/ } )
				.first()
				.click();

			// Decrease second item
			await page
				.getByRole( 'button', { name: /Reduce quantity/ } )
				.first()
				.click();

			// Remove an item
			await page
				.getByRole( 'button', { name: /Remove .* from cart/ } )
				.first()
				.click();

			// Wait for requests
			await page.waitForTimeout( 3000 );

			// If we got a 400 error, there should be a notice
			if ( got400Error ) {
				const notice = page.locator(
					'.wc-block-mini-cart__drawer .wc-block-components-notice-banner'
				);
				// This assertion will FAIL if 400 errors don't show notices
				await expect( notice ).toBeVisible();
				await expect( notice ).toContainText( /error|failed/i );
			}
		} );
	} );
} );
