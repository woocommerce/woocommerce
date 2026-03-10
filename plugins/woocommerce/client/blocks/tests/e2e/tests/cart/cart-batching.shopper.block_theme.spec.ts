/**
 * External dependencies
 */
import { test as base, expect } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { CartPage } from './cart.page';
import {
	SIMPLE_PHYSICAL_PRODUCT_NAME,
	SIMPLE_VIRTUAL_PRODUCT_NAME,
} from '../checkout/constants';

const test = base.extend< { pageObject: CartPage } >( {
	pageObject: async ( { page }, use ) => {
		const pageObject = new CartPage( { page } );
		await use( pageObject );
	},
} );

test.describe( 'Cart Request Batching', () => {
	test( 'Same-tick add-to-cart clicks are batched into a single batch request', async ( {
		page,
		frontendUtils,
		pageObject,
	} ) => {
		await frontendUtils.goToShop();

		// Intercept batch requests to count them and inspect their contents.
		const batchBodies: Array< { requests: Array< { path: string } > } > =
			[];
		await page.route( '**/wc/store/v1/batch**', async ( route ) => {
			const postData = route.request().postData();
			if ( postData ) {
				batchBodies.push( JSON.parse( postData ) );
			}
			await route.continue();
		} );

		// Set up a promise to wait for the first batch response.
		const batchResponsePromise = page.waitForResponse(
			( response ) =>
				response.url().includes( '/wc/store/v1/batch' ) &&
				response.status() === 207
		);

		// Click two add-to-cart buttons synchronously in the same tick
		// using page.evaluate so they are not separated by async waits.
		await page.evaluate(
			( [ productA, productB ] ) => {
				const btnA = document.querySelector(
					`[aria-label='Add to cart: \u201C${ productA }\u201D']`
				);
				const btnB = document.querySelector(
					`[aria-label='Add to cart: \u201C${ productB }\u201D']`
				);
				if ( btnA instanceof HTMLElement ) btnA.click();
				if ( btnB instanceof HTMLElement ) btnB.click();
			},
			[
				SIMPLE_PHYSICAL_PRODUCT_NAME,
				SIMPLE_VIRTUAL_PRODUCT_NAME,
			] as const
		);

		// Wait for the batch response to complete.
		await batchResponsePromise;

		// Verify exactly one batch request was sent containing both operations.
		expect( batchBodies ).toHaveLength( 1 );

		const addItemRequests = batchBodies[ 0 ].requests.filter( ( r ) =>
			r.path.includes( '/cart/add-item' )
		);
		expect( addItemRequests ).toHaveLength( 2 );

		// Navigate to cart and verify both products are present.
		await frontendUtils.goToCart();

		const beanieRow = await pageObject.findProductRow(
			SIMPLE_PHYSICAL_PRODUCT_NAME
		);
		await expect( beanieRow ).toBeVisible();

		const albumRow = await pageObject.findProductRow(
			SIMPLE_VIRTUAL_PRODUCT_NAME
		);
		await expect( albumRow ).toBeVisible();
	} );

	test( 'Cart reverts to pre-mutation state when batch request fails (total failure rollback)', async ( {
		page,
		frontendUtils,
		pageObject,
	} ) => {
		// Add a product to cart and navigate to the cart page.
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( SIMPLE_PHYSICAL_PRODUCT_NAME );
		await frontendUtils.goToCart();

		// Verify the product is in the cart.
		const productRow = await pageObject.findProductRow(
			SIMPLE_PHYSICAL_PRODUCT_NAME
		);
		await expect( productRow ).toBeVisible();
		await expect(
			page.getByLabel(
				`Quantity of ${ SIMPLE_PHYSICAL_PRODUCT_NAME } in your cart.`
			)
		).toHaveValue( '1' );

		// Abort all batch requests to simulate a network failure.
		await page.route( '**/wc/store/v1/batch**', ( route ) => {
			route.abort( 'failed' );
		} );

		// Attempt to remove the product from the cart.
		await page
			.getByLabel( `Remove ${ SIMPLE_PHYSICAL_PRODUCT_NAME } from cart` )
			.click();

		// After the failed batch, the cart should revert to the original state:
		// the product should still be visible with quantity 1.
		await expect(
			page.getByLabel(
				`Quantity of ${ SIMPLE_PHYSICAL_PRODUCT_NAME } in your cart.`
			)
		).toHaveValue( '1', { timeout: 10000 } );
		await expect( productRow ).toBeVisible();
	} );

	test( 'Error notice appears when a batch sub-request fails (partial failure)', async ( {
		page,
		frontendUtils,
		pageObject,
	} ) => {
		// Add a product to cart and navigate to the cart page.
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( SIMPLE_PHYSICAL_PRODUCT_NAME );
		await frontendUtils.goToCart();

		// Verify the product is in the cart.
		const productRow = await pageObject.findProductRow(
			SIMPLE_PHYSICAL_PRODUCT_NAME
		);
		await expect( productRow ).toBeVisible();

		const errorMessage = 'Could not remove item from cart.';

		// Intercept the next batch request and fulfill with a mock 207
		// response containing a failed sub-response (status 400).
		await page.route(
			'**/wc/store/v1/batch**',
			async ( route ) => {
				await route.fulfill( {
					status: 207,
					contentType: 'application/json',
					body: JSON.stringify( {
						responses: [
							{
								status: 400,
								body: {
									code: 'woocommerce_rest_cart_error',
									message: errorMessage,
									data: { status: 400 },
								},
								headers: {},
							},
						],
					} ),
				} );
			}
		);

		// Trigger a cart mutation (remove product) that will receive
		// the mocked error sub-response.
		await page
			.getByLabel(
				`Remove ${ SIMPLE_PHYSICAL_PRODUCT_NAME } from cart`
			)
			.click();

		// Verify the error notice banner appears with the error message.
		await expect(
			page
				.locator( '.wc-block-components-notice-banner.is-error' )
				.getByText( errorMessage )
		).toBeVisible( { timeout: 10000 } );
	} );

	test( 'Successful batch updates cart state with correct server values', async ( {
		page,
		frontendUtils,
		pageObject,
	} ) => {
		// Add a product to cart and navigate to the cart page.
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( SIMPLE_PHYSICAL_PRODUCT_NAME );
		await frontendUtils.goToCart();

		// Verify the product is in the cart with quantity 1.
		const productRow = await pageObject.findProductRow(
			SIMPLE_PHYSICAL_PRODUCT_NAME
		);
		await expect( productRow ).toBeVisible();
		const quantityInput = page.getByLabel(
			`Quantity of ${ SIMPLE_PHYSICAL_PRODUCT_NAME } in your cart.`
		);
		await expect( quantityInput ).toHaveValue( '1' );

		// Click the increase quantity button to trigger a batch update.
		await page
			.getByLabel(
				`Increase quantity of ${ SIMPLE_PHYSICAL_PRODUCT_NAME }`
			)
			.click();

		// Wait for the checkout button to become enabled, indicating
		// the batch response has been processed and cart state updated.
		await expect(
			page.getByRole( 'link', { name: 'Proceed to Checkout' } )
		).toBeEnabled( { timeout: 10000 } );

		// Verify the quantity input reflects the server-confirmed value.
		await expect( quantityInput ).toHaveValue( '2' );

		// Verify the product row is still visible with updated state.
		await expect( productRow ).toBeVisible();
	} );

	test( 'Side effects fire after successful add-to-cart (custom event and a11y)', async ( {
		page,
		frontendUtils,
	} ) => {
		await frontendUtils.goToShop();

		// Set up a listener for the wc-blocks_added_to_cart custom event
		// before triggering the add-to-cart action.
		await page.evaluate( () => {
			const w = window as Window & Record< string, unknown >;
			w.__addedToCartFired = false;
			w.__addedToCartDetail = null;
			document.body.addEventListener(
				'wc-blocks_added_to_cart',
				( e ) => {
					w.__addedToCartFired = true;
					w.__addedToCartDetail = ( e as CustomEvent ).detail;
				}
			);
		} );

		// Add a product to cart — this triggers the batch request which,
		// on success, should fire the custom event and a11y announcement.
		await frontendUtils.addToCart( SIMPLE_PHYSICAL_PRODUCT_NAME );

		// Verify the wc-blocks_added_to_cart event was fired.
		const eventFired = await page.evaluate(
			() =>
				( window as Window & Record< string, unknown > )
					.__addedToCartFired
		);
		expect( eventFired ).toBe( true );

		// Verify the event detail includes preserveCartData: true.
		const detail = await page.evaluate(
			() =>
				( window as Window & Record< string, unknown > )
					.__addedToCartDetail
		);
		expect( detail ).toEqual(
			expect.objectContaining( { preserveCartData: true } )
		);

		// Verify the a11y announcement was made (screen reader region
		// should have content after a successful add).
		await expect(
			page.locator( '#a11y-speak-polite' )
		).not.toBeEmpty( { timeout: 5000 } );
	} );

	test( 'Operations during in-flight batch are queued and sent in a subsequent batch', async ( {
		page,
		frontendUtils,
		pageObject,
	} ) => {
		await frontendUtils.goToShop();

		// Track batch requests and hold the first one so we can queue
		// a second operation while it is in-flight.
		let batchCount = 0;
		let resolveFirstBatchArrived: () => void;
		const firstBatchArrived = new Promise< void >( ( r ) => {
			resolveFirstBatchArrived = r;
		} );
		let releaseFirstBatch: () => void;
		const firstBatchGate = new Promise< void >( ( r ) => {
			releaseFirstBatch = r;
		} );
		let resolveSecondBatchArrived: () => void;
		const secondBatchArrived = new Promise< void >( ( r ) => {
			resolveSecondBatchArrived = r;
		} );

		await page.route( '**/wc/store/v1/batch**', async ( route ) => {
			batchCount++;
			if ( batchCount === 1 ) {
				// Signal that the first batch has arrived, then wait
				// for the test to release it.
				resolveFirstBatchArrived();
				await firstBatchGate;
			}
			if ( batchCount === 2 ) {
				resolveSecondBatchArrived();
			}
			await route.continue();
		} );

		// Click the first add-to-cart button. This triggers the first
		// batch request which will be held by the route handler.
		await page.evaluate( ( productName ) => {
			const btn = document.querySelector(
				`[aria-label='Add to cart: \u201C${ productName }\u201D']`
			);
			if ( btn instanceof HTMLElement ) btn.click();
		}, SIMPLE_PHYSICAL_PRODUCT_NAME );

		// Wait until the first batch request has arrived at the server.
		await firstBatchArrived;

		// While the first batch is in-flight, trigger another
		// add-to-cart. This operation should be queued and sent
		// in a subsequent batch.
		await page.evaluate( ( productName ) => {
			const btn = document.querySelector(
				`[aria-label='Add to cart: \u201C${ productName }\u201D']`
			);
			if ( btn instanceof HTMLElement ) btn.click();
		}, SIMPLE_VIRTUAL_PRODUCT_NAME );

		// Release the first batch so it can complete.
		releaseFirstBatch!();

		// Wait for the second batch request to arrive, confirming
		// the queued operation was sent as a separate batch.
		await secondBatchArrived;

		// Verify at least 2 separate batch requests were made.
		expect( batchCount ).toBeGreaterThanOrEqual( 2 );

		// Navigate to cart and verify both products are present,
		// confirming both batches were processed successfully.
		await frontendUtils.goToCart();

		const beanieRow = await pageObject.findProductRow(
			SIMPLE_PHYSICAL_PRODUCT_NAME
		);
		await expect( beanieRow ).toBeVisible();

		const albumRow = await pageObject.findProductRow(
			SIMPLE_VIRTUAL_PRODUCT_NAME
		);
		await expect( albumRow ).toBeVisible();
	} );

	test( 'Optimistic updates are visible before server responds', async ( {
		page,
		frontendUtils,
		pageObject,
	} ) => {
		// Add a product to cart and navigate to the cart page.
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( SIMPLE_PHYSICAL_PRODUCT_NAME );
		await frontendUtils.goToCart();

		// Verify the product is in the cart with quantity 1.
		const productRow = await pageObject.findProductRow(
			SIMPLE_PHYSICAL_PRODUCT_NAME
		);
		await expect( productRow ).toBeVisible();
		const quantityInput = page.getByLabel(
			`Quantity of ${ SIMPLE_PHYSICAL_PRODUCT_NAME } in your cart.`
		);
		await expect( quantityInput ).toHaveValue( '1' );

		// Hold the batch response so we can observe the optimistic UI
		// state before the server responds.
		let releaseBatch: () => void;
		const batchHeld = new Promise< void >( ( r ) => {
			releaseBatch = r;
		} );

		await page.route( '**/wc/store/v1/batch**', async ( route ) => {
			await batchHeld;
			await route.continue();
		} );

		// Click the increase quantity button. The UI should update
		// optimistically before the batch response arrives.
		await page
			.getByLabel(
				`Increase quantity of ${ SIMPLE_PHYSICAL_PRODUCT_NAME }`
			)
			.click();

		// Verify the quantity input shows 2 immediately (optimistic
		// update) even though the batch response has not yet returned.
		await expect( quantityInput ).toHaveValue( '2' );

		// Release the held batch response so the server can confirm.
		releaseBatch!();

		// Wait for the checkout button to become enabled, indicating
		// the batch response has been processed.
		await expect(
			page.getByRole( 'link', { name: 'Proceed to Checkout' } )
		).toBeEnabled( { timeout: 10000 } );

		// Verify the final state matches — quantity remains 2 after
		// server confirmation.
		await expect( quantityInput ).toHaveValue( '2' );
		await expect( productRow ).toBeVisible();
	} );

	test( 'Cross-sells add-to-cart goes through batch endpoint', async ( {
		page,
		frontendUtils,
		pageObject,
	} ) => {
		// Add Beanie to cart (it has Cap as a cross-sell product).
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( SIMPLE_PHYSICAL_PRODUCT_NAME );
		await frontendUtils.goToCart();

		// Wait for the cross-sells product collection to load.
		await page
			.locator( '.wp-block-woocommerce-product-collection' )
			.waitFor();

		// Intercept batch requests to verify the cross-sell add goes
		// through the batch endpoint.
		const batchBodies: Array< { requests: Array< { path: string } > } > =
			[];
		await page.route( '**/wc/store/v1/batch**', async ( route ) => {
			const postData = route.request().postData();
			if ( postData ) {
				batchBodies.push( JSON.parse( postData ) );
			}
			await route.continue();
		} );

		// Set up a promise to wait for the batch response.
		const batchResponsePromise = page.waitForResponse(
			( response ) =>
				response.url().includes( '/wc/store/v1/batch' ) &&
				response.status() === 207
		);

		// Click the cross-sell "Add to cart" button for Cap.
		// The cross-sells button only shows "Add to cart" without the
		// product name, so scope the click within the cross-sells section.
		await page
			.locator( '.wp-block-woocommerce-product-collection' )
			.getByRole( 'button', { name: 'Add to cart' } )
			.click();

		// Wait for the batch response to complete.
		await batchResponsePromise;

		// Verify the add went through the batch endpoint.
		expect( batchBodies.length ).toBeGreaterThanOrEqual( 1 );
		const lastBatch = batchBodies[ batchBodies.length - 1 ];
		const addItemRequests = lastBatch.requests.filter( ( r ) =>
			r.path.includes( '/cart/add-item' )
		);
		expect( addItemRequests ).toHaveLength( 1 );

		// Verify Cap appears in the cart with quantity 1.
		const capRow = await pageObject.findProductRow( 'Cap' );
		await expect( capRow ).toBeVisible();
		await expect(
			page.getByLabel( 'Quantity of Cap in your cart.' )
		).toHaveValue( '1' );
	} );
} );
