/**
 * External dependencies
 */
import { Page, Locator, Request, Response } from '@playwright/test';
import { RequestUtils } from '@wordpress/e2e-test-utils-playwright';

const wait = ( time: number ) =>
	new Promise( ( resolve ) => setTimeout( resolve, time ) );

/**
 * Custom waitForFunction implementation that runs in Node.js context.
 *
 * Unlike page.waitForFunction() which executes in the browser context and can
 * only access serializable values passed as arguments, this function runs in
 * the Node.js context and has full access to closures and local variables. This
 * allows us to directly reference variables without serialization limitations.
 */
async function waitForFunction(
	predicateFunction: () => boolean,
	timeout = 5000,
	interval = 100
) {
	// Lint is too grabby in this case, this usage is fine
	// eslint-disable-next-line @wordpress/no-unused-vars-before-return
	const startTime = performance.now();
	do {
		if ( predicateFunction() ) {
			return true;
		}
		await wait( interval );
	} while ( performance.now() - startTime < timeout );

	throw new Error(
		`Timeout reached after ${ timeout }ms waiting for condition to be met.`
	);
}

const STORE_API_CART_WRITE_REQUEST_URLS = [
	'/cart/add-item',
	'/batch',
	'/cart/remove-item',
	'/cart/update-item',
	'/cart/apply-coupon/',
	'/cart/remove-coupon/',
	'/cart/update-customer',
	'/cart/select-shipping-rate',
];

export class FrontendUtils {
	page: Page;
	requestUtils: RequestUtils;

	constructor( page: Page, requestUtils: RequestUtils ) {
		this.page = page;
		this.requestUtils = requestUtils;
	}

	async getBlockByName( name: string, parentSelector?: string ) {
		let selector = `[data-block-name="${ name }"]`;
		if ( parentSelector ) {
			selector = `${ parentSelector } [data-block-name="${ name }"]`;
		}
		return this.page.locator( selector );
	}

	/**
	 * Start tracking cart-related requests and return a function to wait for completion
	 */
	private trackCartRequests( timeout = 5000 ) {
		// key: request url, value: count of pending requests with this url
		const pendingRequests = new Map< string, number >();

		const requestHandler = ( request: Request ) => {
			const url = request.url();
			if (
				STORE_API_CART_WRITE_REQUEST_URLS.some( ( cartUrl ) =>
					url.includes( cartUrl )
				)
			) {
				pendingRequests.set(
					url,
					( pendingRequests.get( url ) ?? 0 ) + 1
				);
			}
		};

		const responseHandler = ( response: Response ) => {
			const url = response.url();
			const pendingRequestCount = pendingRequests.get( url );

			// means we're not dealing with cart request
			if ( pendingRequestCount === undefined ) {
				return;
			}

			if ( pendingRequestCount === 1 ) {
				pendingRequests.delete( url );
				return;
			}

			pendingRequests.set( url, pendingRequestCount - 1 );
		};

		this.page.on( 'request', requestHandler );
		this.page.on( 'response', responseHandler );

		return {
			waitForCartRequests: async () => {
				try {
					await waitForFunction(
						() => pendingRequests.size === 0,
						timeout
					);
				} finally {
					this.page.off( 'request', requestHandler );
					this.page.off( 'response', responseHandler );
				}
			},
		};
	}

	async addToCart( itemName = '' ) {
		// Start tracking cart requests before the action
		const { waitForCartRequests } = this.trackCartRequests();

		if ( itemName !== '' ) {
			// We can't use `getByRole()` here because the Add to Cart button
			// might be a button (in blocks) or a link (in the legacy template).
			await this.page
				.getByLabel( `Add to cart: “${ itemName }”` )
				.click();
		} else {
			await this.page.click( 'text=Add to cart' );
		}

		// Wait for the cart request triggered by this action to complete
		// We do it the complex way as there are no visual cues that we can rely on
		await waitForCartRequests();
	}

	async goToCheckout() {
		await this.page.goto( '/checkout' );
	}

	async goToCart() {
		await this.page.goto( '/cart' );
	}

	async goToCartShortcode() {
		await this.page.goto( '/cart-shortcode' );
	}

	async goToMiniCart() {
		await this.page.goto( '/mini-cart' );
	}

	async goToShop() {
		await this.page.goto( '/shop' );
	}

	async emptyCart() {
		const cartResponse = await this.requestUtils.request.get(
			'/wp-json/wc/store/cart'
		);
		const nonce = cartResponse.headers()?.nonce;
		if ( ! nonce ) {
			throw new Error( 'Could not get cart nonce.' );
		}
		const res = await this.requestUtils.request.delete(
			'/wp-json/wc/store/v1/cart/items',
			{ headers: { nonce } }
		);
		if ( ! res.ok() ) {
			throw new Error(
				`Got an error response when trying to empty cart. Status code: ${ res.status() }`
			);
		}
	}

	/**
	 * Playwright selectText causes flaky tests when running on local
	 * development machine. This method is more reliable on both environments.
	 */
	async selectTextInput( locator: Locator ) {
		await locator.click();
		await locator.press( 'End' );
		await locator.press( 'Shift+Home' );
	}

	async gotoMyAccount() {
		await this.page.goto( '/my-account' );
	}
}
