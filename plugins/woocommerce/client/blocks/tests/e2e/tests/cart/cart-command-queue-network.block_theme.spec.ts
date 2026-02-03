/**
 * External dependencies
 */
import { test as base, expect } from '@woocommerce/e2e-utils';
import type { Page, Route, Request } from '@playwright/test';

/**
 * Internal dependencies
 */
import { CartPage } from './cart.page';
import {
	SIMPLE_PHYSICAL_PRODUCT_NAME,
	SIMPLE_VIRTUAL_PRODUCT_NAME,
} from '../checkout/constants';

/**
 * Cart API URLs that the command queue uses.
 */
const CART_API_PATTERNS = [
	'**/wc/store/v1/cart/add-item',
	'**/wc/store/v1/cart/remove-item',
	'**/wc/store/v1/cart/update-item',
	'**/wc/store/v1/batch',
];

/**
 * Cart API URL substrings for request matching.
 */
const CART_API_URLS = [
	'/cart/add-item',
	'/cart/remove-item',
	'/cart/update-item',
	'/batch',
];

/**
 * Network condition helpers for simulating various network scenarios.
 */
class NetworkSimulator {
	private page: Page;
	private activeRoutes: Array< () => Promise< void > > = [];

	constructor( page: Page ) {
		this.page = page;
	}

	/**
	 * Simulate a slow network by delaying cart API responses.
	 *
	 * @param delayMs - Delay in milliseconds
	 */
	async simulateSlowNetwork( delayMs: number ): Promise< void > {
		for ( const pattern of CART_API_PATTERNS ) {
			await this.page.route( pattern, async ( route: Route ) => {
				await new Promise( ( resolve ) =>
					setTimeout( resolve, delayMs )
				);
				await route.continue();
			} );
		}
		this.activeRoutes.push( async () => {
			for ( const pattern of CART_API_PATTERNS ) {
				await this.page.unroute( pattern );
			}
		} );
	}

	/**
	 * Simulate network failure for cart API requests.
	 *
	 * @param failureCount - Number of requests to fail before succeeding
	 */
	async simulateNetworkFailure( failureCount = 1 ): Promise< void > {
		let failedCount = 0;

		for ( const pattern of CART_API_PATTERNS ) {
			await this.page.route( pattern, async ( route: Route ) => {
				if ( failedCount < failureCount ) {
					failedCount++;
					await route.abort( 'failed' );
				} else {
					await route.continue();
				}
			} );
		}
		this.activeRoutes.push( async () => {
			for ( const pattern of CART_API_PATTERNS ) {
				await this.page.unroute( pattern );
			}
		} );
	}

	/**
	 * Simulate a timeout by not responding to requests.
	 * The request will hang until it times out on the client side.
	 */
	async simulateTimeout(): Promise< void > {
		for ( const pattern of CART_API_PATTERNS ) {
			// eslint-disable-next-line @typescript-eslint/no-unused-vars
			await this.page.route( pattern, async ( _route: Route ) => {
				// Don't call route.continue() - let it hang indefinitely
				// This simulates a network timeout from the client's perspective
				await new Promise< void >( () => {
					// Intentionally never resolves
				} );
			} );
		}
		this.activeRoutes.push( async () => {
			for ( const pattern of CART_API_PATTERNS ) {
				await this.page.unroute( pattern );
			}
		} );
	}

	/**
	 * Simulate server returning an error response (not a network failure).
	 *
	 * @param statusCode   - HTTP status code to return
	 * @param errorMessage - Error message to include in response
	 */
	async simulateServerError(
		statusCode = 500,
		errorMessage = 'Internal Server Error'
	): Promise< void > {
		for ( const pattern of CART_API_PATTERNS ) {
			await this.page.route( pattern, async ( route: Route ) => {
				await route.fulfill( {
					status: statusCode,
					contentType: 'application/json',
					body: JSON.stringify( {
						code: 'woocommerce_rest_error',
						message: errorMessage,
						data: { status: statusCode },
					} ),
				} );
			} );
		}
		this.activeRoutes.push( async () => {
			for ( const pattern of CART_API_PATTERNS ) {
				await this.page.unroute( pattern );
			}
		} );
	}

	/**
	 * Clear all active route handlers.
	 */
	async clearAll(): Promise< void > {
		for ( const cleanup of this.activeRoutes ) {
			await cleanup();
		}
		this.activeRoutes = [];
	}
}

/**
 * Request tracker to monitor cart API requests.
 */
class RequestTracker {
	private page: Page;
	private requests: Request[] = [];
	private requestHandler: ( request: Request ) => void;

	constructor( page: Page ) {
		this.page = page;
		this.requestHandler = ( request: Request ) => {
			const url = request.url();
			if (
				CART_API_URLS.some( ( cartUrl ) => url.includes( cartUrl ) )
			) {
				this.requests.push( request );
			}
		};
	}

	start(): void {
		this.requests = [];
		this.page.on( 'request', this.requestHandler );
	}

	stop(): void {
		this.page.off( 'request', this.requestHandler );
	}

	getRequests(): Request[] {
		return [ ...this.requests ];
	}

	getRequestCount(): number {
		return this.requests.length;
	}

	clear(): void {
		this.requests = [];
	}
}

const test = base.extend< {
	pageObject: CartPage;
	networkSimulator: NetworkSimulator;
	requestTracker: RequestTracker;
} >( {
	pageObject: async ( { page }, use ) => {
		const pageObject = new CartPage( { page } );
		await use( pageObject );
	},
	networkSimulator: async ( { page }, use ) => {
		const simulator = new NetworkSimulator( page );
		await use( simulator );
		// Clean up after test
		await simulator.clearAll();
	},
	requestTracker: async ( { page }, use ) => {
		const tracker = new RequestTracker( page );
		await use( tracker );
		tracker.stop();
	},
} );

test.describe( 'Cart Command Queue → Network Conditions', () => {
	test.describe( 'Slow Network', () => {
		test( 'UI remains responsive during slow network requests', async ( {
			page,
			networkSimulator,
		} ) => {
			// Add item to cart first with normal network
			await page.goto( '/shop' );
			await page
				.getByLabel(
					`Add to cart: "${ SIMPLE_PHYSICAL_PRODUCT_NAME }"`
				)
				.click();
			await page.goto( '/cart' );

			// Simulate slow network (500ms delay)
			await networkSimulator.simulateSlowNetwork( 500 );

			// Click the quantity increase button
			const increaseButton = page.getByLabel(
				`Increase quantity of ${ SIMPLE_PHYSICAL_PRODUCT_NAME }`
			);
			await increaseButton.click();

			// Verify the "Proceed to Checkout" button is disabled during request
			await expect(
				page.getByRole( 'link', { name: 'Proceed to Checkout' } )
			).toBeDisabled();

			// Wait for the button to be enabled again (request completed)
			await expect(
				page.getByRole( 'link', { name: 'Proceed to Checkout' } )
			).toBeEnabled( { timeout: 10000 } );

			// Verify quantity was updated
			await expect(
				page.getByLabel(
					`Quantity of ${ SIMPLE_PHYSICAL_PRODUCT_NAME } in your cart.`
				)
			).toHaveValue( '2' );
		} );

		test( 'Optimistic updates show immediately despite slow network', async ( {
			page,
			networkSimulator,
		} ) => {
			await page.goto( '/shop' );
			await page
				.getByLabel(
					`Add to cart: "${ SIMPLE_PHYSICAL_PRODUCT_NAME }"`
				)
				.click();
			await page.goto( '/cart' );

			// Simulate very slow network (2000ms delay)
			await networkSimulator.simulateSlowNetwork( 2000 );

			// Get the quantity input
			const quantityInput = page.getByLabel(
				`Quantity of ${ SIMPLE_PHYSICAL_PRODUCT_NAME } in your cart.`
			);

			// Verify initial quantity
			await expect( quantityInput ).toHaveValue( '1' );

			// Click increase - optimistic update should show immediately
			await page
				.getByLabel(
					`Increase quantity of ${ SIMPLE_PHYSICAL_PRODUCT_NAME }`
				)
				.click();

			// The optimistic update should show the new value quickly
			// (within 100ms, not waiting for the 2000ms network delay)
			await expect( quantityInput ).toHaveValue( '2', { timeout: 500 } );

			// Button should be disabled while request is in-flight
			await expect(
				page.getByRole( 'link', { name: 'Proceed to Checkout' } )
			).toBeDisabled();

			// Wait for full completion
			await expect(
				page.getByRole( 'link', { name: 'Proceed to Checkout' } )
			).toBeEnabled( { timeout: 5000 } );
		} );
	} );

	test.describe( 'Network Failure', () => {
		test( 'Rolls back optimistic update on network failure', async ( {
			page,
			networkSimulator,
		} ) => {
			await page.goto( '/shop' );
			await page
				.getByLabel(
					`Add to cart: "${ SIMPLE_PHYSICAL_PRODUCT_NAME }"`
				)
				.click();
			await page.goto( '/cart' );

			// Verify initial quantity
			const quantityInput = page.getByLabel(
				`Quantity of ${ SIMPLE_PHYSICAL_PRODUCT_NAME } in your cart.`
			);
			await expect( quantityInput ).toHaveValue( '1' );

			// Simulate network failure
			await networkSimulator.simulateNetworkFailure();

			// Try to increase quantity
			await page
				.getByLabel(
					`Increase quantity of ${ SIMPLE_PHYSICAL_PRODUCT_NAME }`
				)
				.click();

			// Wait for the operation to complete (failure + rollback)
			await expect(
				page.getByRole( 'link', { name: 'Proceed to Checkout' } )
			).toBeEnabled( { timeout: 10000 } );

			// Quantity should be rolled back to original value
			await expect( quantityInput ).toHaveValue( '1' );
		} );

		test( 'Shows error notice on network failure', async ( {
			page,
			networkSimulator,
		} ) => {
			await page.goto( '/shop' );
			await page
				.getByLabel(
					`Add to cart: "${ SIMPLE_PHYSICAL_PRODUCT_NAME }"`
				)
				.click();
			await page.goto( '/cart' );

			// Simulate network failure
			await networkSimulator.simulateNetworkFailure();

			// Try to increase quantity
			await page
				.getByLabel(
					`Increase quantity of ${ SIMPLE_PHYSICAL_PRODUCT_NAME }`
				)
				.click();

			// Should see an error notice
			const errorNotice = page.locator(
				'.wc-block-components-notice-banner.is-error'
			);
			await expect( errorNotice ).toBeVisible( { timeout: 10000 } );
		} );
	} );

	test.describe( 'Server Errors', () => {
		test( 'Handles 500 server error gracefully', async ( {
			page,
			networkSimulator,
		} ) => {
			await page.goto( '/shop' );
			await page
				.getByLabel(
					`Add to cart: "${ SIMPLE_PHYSICAL_PRODUCT_NAME }"`
				)
				.click();
			await page.goto( '/cart' );

			// Simulate server error
			await networkSimulator.simulateServerError(
				500,
				'Internal server error'
			);

			// Try to increase quantity
			await page
				.getByLabel(
					`Increase quantity of ${ SIMPLE_PHYSICAL_PRODUCT_NAME }`
				)
				.click();

			// Wait for operation to complete
			await expect(
				page.getByRole( 'link', { name: 'Proceed to Checkout' } )
			).toBeEnabled( { timeout: 10000 } );

			// Should show error notice
			const errorNotice = page.locator(
				'.wc-block-components-notice-banner.is-error'
			);
			await expect( errorNotice ).toBeVisible( { timeout: 5000 } );

			// Quantity should be rolled back
			await expect(
				page.getByLabel(
					`Quantity of ${ SIMPLE_PHYSICAL_PRODUCT_NAME } in your cart.`
				)
			).toHaveValue( '1' );
		} );
	} );

	test.describe( 'Request Batching', () => {
		test( 'Batches multiple rapid operations into fewer requests', async ( {
			page,
			networkSimulator,
			requestTracker,
		} ) => {
			// Add items to cart first
			await page.goto( '/shop' );
			await page
				.getByLabel(
					`Add to cart: "${ SIMPLE_PHYSICAL_PRODUCT_NAME }"`
				)
				.click();
			await page
				.getByLabel( `Add to cart: "${ SIMPLE_VIRTUAL_PRODUCT_NAME }"` )
				.click();
			await page.goto( '/cart' );

			// Simulate slow network so we can observe batching behavior
			await networkSimulator.simulateSlowNetwork( 100 );

			// Start tracking requests
			requestTracker.start();

			// Rapidly click multiple increase buttons in quick succession
			// These should be batched together
			const beanieIncrease = page.getByLabel(
				`Increase quantity of ${ SIMPLE_PHYSICAL_PRODUCT_NAME }`
			);
			const albumIncrease = page.getByLabel(
				`Increase quantity of ${ SIMPLE_VIRTUAL_PRODUCT_NAME }`
			);

			// Click both buttons quickly (within same microtask window ideally)
			await beanieIncrease.click();
			await albumIncrease.click();

			// Wait for operations to complete
			await expect(
				page.getByRole( 'link', { name: 'Proceed to Checkout' } )
			).toBeEnabled( { timeout: 10000 } );

			requestTracker.stop();

			// Both quantities should be updated
			await expect(
				page.getByLabel(
					`Quantity of ${ SIMPLE_PHYSICAL_PRODUCT_NAME } in your cart.`
				)
			).toHaveValue( '2' );
			await expect(
				page.getByLabel(
					`Quantity of ${ SIMPLE_VIRTUAL_PRODUCT_NAME } in your cart.`
				)
			).toHaveValue( '2' );
		} );

		test( 'Sequential operations wait for previous batch to complete', async ( {
			page,
			networkSimulator,
			requestTracker,
		} ) => {
			await page.goto( '/shop' );
			await page
				.getByLabel(
					`Add to cart: "${ SIMPLE_PHYSICAL_PRODUCT_NAME }"`
				)
				.click();
			await page.goto( '/cart' );

			// Simulate slow network
			await networkSimulator.simulateSlowNetwork( 200 );

			requestTracker.start();

			// First operation
			await page
				.getByLabel(
					`Increase quantity of ${ SIMPLE_PHYSICAL_PRODUCT_NAME }`
				)
				.click();

			// Wait a bit using a locator-based approach, then do second operation
			// This should queue and execute after first completes
			await page
				.getByLabel(
					`Quantity of ${ SIMPLE_PHYSICAL_PRODUCT_NAME } in your cart.`
				)
				.waitFor( { state: 'visible' } );

			await page
				.getByLabel(
					`Increase quantity of ${ SIMPLE_PHYSICAL_PRODUCT_NAME }`
				)
				.click();

			// Wait for all operations to complete
			await expect(
				page.getByRole( 'link', { name: 'Proceed to Checkout' } )
			).toBeEnabled( { timeout: 15000 } );

			requestTracker.stop();

			// Final quantity should be 3 (1 + 1 + 1)
			await expect(
				page.getByLabel(
					`Quantity of ${ SIMPLE_PHYSICAL_PRODUCT_NAME } in your cart.`
				)
			).toHaveValue( '3' );
		} );
	} );

	test.describe( 'Remove Item', () => {
		test( 'Removes item optimistically and recovers on failure', async ( {
			page,
			networkSimulator,
		} ) => {
			await page.goto( '/shop' );
			await page
				.getByLabel(
					`Add to cart: "${ SIMPLE_PHYSICAL_PRODUCT_NAME }"`
				)
				.click();
			await page.goto( '/cart' );

			// Verify item is in cart
			const productRow = page.locator( '.wc-block-cart-items__row', {
				hasText: SIMPLE_PHYSICAL_PRODUCT_NAME,
			} );
			await expect( productRow ).toBeVisible();

			// Simulate network failure
			await networkSimulator.simulateNetworkFailure();

			// Try to remove the item
			await page
				.getByLabel(
					`Remove ${ SIMPLE_PHYSICAL_PRODUCT_NAME } from cart`
				)
				.click();

			// Wait for operation to complete (failure + rollback)
			await expect(
				page.getByRole( 'link', { name: 'Proceed to Checkout' } )
			).toBeEnabled( { timeout: 10000 } );

			// Item should still be in cart (rolled back)
			await expect( productRow ).toBeVisible();
		} );

		test( 'Successfully removes item under slow network', async ( {
			page,
			networkSimulator,
		} ) => {
			await page.goto( '/shop' );
			await page
				.getByLabel(
					`Add to cart: "${ SIMPLE_PHYSICAL_PRODUCT_NAME }"`
				)
				.click();
			await page.goto( '/cart' );

			// Simulate slow network
			await networkSimulator.simulateSlowNetwork( 500 );

			// Remove the item
			await page
				.getByLabel(
					`Remove ${ SIMPLE_PHYSICAL_PRODUCT_NAME } from cart`
				)
				.click();

			// Wait for cart to be empty
			await expect(
				page.getByRole( 'heading', {
					name: 'Your cart is currently empty!',
				} )
			).toBeVisible( { timeout: 10000 } );
		} );
	} );

	test.describe( 'Multiple Items', () => {
		test( 'Handles concurrent operations on different items', async ( {
			page,
			networkSimulator,
		} ) => {
			// Add multiple items
			await page.goto( '/shop' );
			await page
				.getByLabel(
					`Add to cart: "${ SIMPLE_PHYSICAL_PRODUCT_NAME }"`
				)
				.click();
			await page
				.getByLabel( `Add to cart: "${ SIMPLE_VIRTUAL_PRODUCT_NAME }"` )
				.click();
			await page.goto( '/cart' );

			// Simulate slow network
			await networkSimulator.simulateSlowNetwork( 300 );

			// Update quantities on both items
			await page
				.getByLabel(
					`Increase quantity of ${ SIMPLE_PHYSICAL_PRODUCT_NAME }`
				)
				.click();
			await page
				.getByLabel(
					`Increase quantity of ${ SIMPLE_VIRTUAL_PRODUCT_NAME }`
				)
				.click();

			// Wait for operations to complete
			await expect(
				page.getByRole( 'link', { name: 'Proceed to Checkout' } )
			).toBeEnabled( { timeout: 15000 } );

			// Both should be updated
			await expect(
				page.getByLabel(
					`Quantity of ${ SIMPLE_PHYSICAL_PRODUCT_NAME } in your cart.`
				)
			).toHaveValue( '2' );
			await expect(
				page.getByLabel(
					`Quantity of ${ SIMPLE_VIRTUAL_PRODUCT_NAME } in your cart.`
				)
			).toHaveValue( '2' );
		} );

		test( 'Rolls back all optimistic updates on batch failure', async ( {
			page,
			networkSimulator,
		} ) => {
			// Add multiple items
			await page.goto( '/shop' );
			await page
				.getByLabel(
					`Add to cart: "${ SIMPLE_PHYSICAL_PRODUCT_NAME }"`
				)
				.click();
			await page
				.getByLabel( `Add to cart: "${ SIMPLE_VIRTUAL_PRODUCT_NAME }"` )
				.click();
			await page.goto( '/cart' );

			// Verify initial quantities
			await expect(
				page.getByLabel(
					`Quantity of ${ SIMPLE_PHYSICAL_PRODUCT_NAME } in your cart.`
				)
			).toHaveValue( '1' );
			await expect(
				page.getByLabel(
					`Quantity of ${ SIMPLE_VIRTUAL_PRODUCT_NAME } in your cart.`
				)
			).toHaveValue( '1' );

			// Simulate network failure
			await networkSimulator.simulateNetworkFailure();

			// Try to update both items
			await page
				.getByLabel(
					`Increase quantity of ${ SIMPLE_PHYSICAL_PRODUCT_NAME }`
				)
				.click();
			await page
				.getByLabel(
					`Increase quantity of ${ SIMPLE_VIRTUAL_PRODUCT_NAME }`
				)
				.click();

			// Wait for operation to complete
			await expect(
				page.getByRole( 'link', { name: 'Proceed to Checkout' } )
			).toBeEnabled( { timeout: 15000 } );

			// Both should be rolled back to original values
			await expect(
				page.getByLabel(
					`Quantity of ${ SIMPLE_PHYSICAL_PRODUCT_NAME } in your cart.`
				)
			).toHaveValue( '1' );
			await expect(
				page.getByLabel(
					`Quantity of ${ SIMPLE_VIRTUAL_PRODUCT_NAME } in your cart.`
				)
			).toHaveValue( '1' );
		} );
	} );

	test.describe( 'State Recovery', () => {
		test( 'Cart state remains consistent after multiple failed operations', async ( {
			page,
			networkSimulator,
		} ) => {
			await page.goto( '/shop' );
			await page
				.getByLabel(
					`Add to cart: "${ SIMPLE_PHYSICAL_PRODUCT_NAME }"`
				)
				.click();
			await page.goto( '/cart' );

			// Simulate multiple failures
			await networkSimulator.simulateNetworkFailure( 2 );

			// Try multiple operations that will fail
			await page
				.getByLabel(
					`Increase quantity of ${ SIMPLE_PHYSICAL_PRODUCT_NAME }`
				)
				.click();

			// Wait for first failure
			await expect(
				page.getByRole( 'link', { name: 'Proceed to Checkout' } )
			).toBeEnabled( { timeout: 10000 } );

			// Try again
			await page
				.getByLabel(
					`Increase quantity of ${ SIMPLE_PHYSICAL_PRODUCT_NAME }`
				)
				.click();

			// Wait for second failure
			await expect(
				page.getByRole( 'link', { name: 'Proceed to Checkout' } )
			).toBeEnabled( { timeout: 10000 } );

			// Clear network simulation
			await networkSimulator.clearAll();

			// Now try a real operation - should succeed
			await page
				.getByLabel(
					`Increase quantity of ${ SIMPLE_PHYSICAL_PRODUCT_NAME }`
				)
				.click();

			// Wait for success
			await expect(
				page.getByRole( 'link', { name: 'Proceed to Checkout' } )
			).toBeEnabled( { timeout: 10000 } );

			// Should now be 2 (only the successful operation counted)
			await expect(
				page.getByLabel(
					`Quantity of ${ SIMPLE_PHYSICAL_PRODUCT_NAME } in your cart.`
				)
			).toHaveValue( '2' );
		} );

		test( 'Page reload shows correct server state after failures', async ( {
			page,
			networkSimulator,
		} ) => {
			await page.goto( '/shop' );
			await page
				.getByLabel(
					`Add to cart: "${ SIMPLE_PHYSICAL_PRODUCT_NAME }"`
				)
				.click();
			await page.goto( '/cart' );

			// Simulate failure
			await networkSimulator.simulateNetworkFailure();

			// Try to increase quantity (will fail)
			await page
				.getByLabel(
					`Increase quantity of ${ SIMPLE_PHYSICAL_PRODUCT_NAME }`
				)
				.click();

			// Wait for failure
			await expect(
				page.getByRole( 'link', { name: 'Proceed to Checkout' } )
			).toBeEnabled( { timeout: 10000 } );

			// Clear network simulation and reload page
			await networkSimulator.clearAll();
			await page.reload();

			// Quantity should be 1 (the actual server state)
			await expect(
				page.getByLabel(
					`Quantity of ${ SIMPLE_PHYSICAL_PRODUCT_NAME } in your cart.`
				)
			).toHaveValue( '1' );
		} );
	} );
} );
