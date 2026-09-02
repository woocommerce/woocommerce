/**
 * External dependencies
 */
import type { Page, Locator, Request } from '@playwright/test';
import type { RequestUtils } from '@wordpress/e2e-test-utils-playwright';

const CART_WRITE_REQUEST_PATHS = [
	'/wc/store/v1/cart/add-item',
	'/wc/store/v1/cart/remove-item',
	'/wc/store/v1/cart/update-item',
	'/wc/store/v1/cart/apply-coupon',
	'/wc/store/v1/cart/remove-coupon',
	'/wc/store/v1/cart/update-customer',
	'/wc/store/v1/cart/select-shipping-rate',
];
const CART_BATCH_REQUEST_PATH = '/wc/store/v1/batch';
const CART_REQUEST_TIMEOUT = 5000;
const WRITE_REQUEST_METHODS = new Set( [ 'POST', 'PUT', 'PATCH', 'DELETE' ] );

const getPathname = ( url: string ) =>
	new URL( url, 'http://localhost' ).pathname.replace( /\/+$/, '' );

const isWriteMethod = ( method: string ) =>
	WRITE_REQUEST_METHODS.has( method.toUpperCase() );

const isCartWritePath = ( path: string ) =>
	CART_WRITE_REQUEST_PATHS.some( ( cartPath ) =>
		getPathname( path ).endsWith( cartPath )
	);

const isCartWriteRequest = ( request: Request ) => {
	if ( ! isWriteMethod( request.method() ) ) {
		return false;
	}

	const requestUrl = new URL( request.url(), 'http://localhost' );

	if ( requestUrl.searchParams.get( 'wc-ajax' ) === 'add_to_cart' ) {
		return true;
	}

	const requestPath = requestUrl.pathname.replace( /\/+$/, '' );

	if ( isCartWritePath( requestPath ) ) {
		return true;
	}

	if ( ! requestPath.endsWith( CART_BATCH_REQUEST_PATH ) ) {
		return false;
	}

	try {
		const batch = request.postDataJSON() as {
			requests?: Array< { method?: string; path?: string } >;
		};

		return (
			Array.isArray( batch?.requests ) &&
			batch.requests.some(
				( batchRequest ) =>
					typeof batchRequest.method === 'string' &&
					typeof batchRequest.path === 'string' &&
					isWriteMethod( batchRequest.method ) &&
					isCartWritePath( batchRequest.path )
			)
		);
	} catch {
		return false;
	}
};

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

	private async performCartAction( action: () => Promise< void > ) {
		const pendingRequests = new Set< Request >();
		let cartRequestObserved = false;
		let actionCompleted = false;
		let resolveCartRequests!: () => void;
		const cartRequestsCompleted = new Promise< void >( ( resolve ) => {
			resolveCartRequests = resolve;
		} );
		let deadline: ReturnType< typeof setTimeout > | undefined;

		const resolveIfComplete = () => {
			if (
				actionCompleted &&
				cartRequestObserved &&
				pendingRequests.size === 0
			) {
				resolveCartRequests();
			}
		};

		const requestHandler = ( request: Request ) => {
			if ( isCartWriteRequest( request ) ) {
				pendingRequests.add( request );
				cartRequestObserved = true;
			}
		};

		const requestSettledHandler = ( request: Request ) => {
			if ( pendingRequests.delete( request ) ) {
				resolveIfComplete();
			}
		};

		this.page.on( 'request', requestHandler );
		this.page.on( 'requestfinished', requestSettledHandler );
		this.page.on( 'requestfailed', requestSettledHandler );

		try {
			await action();
			actionCompleted = true;
			resolveIfComplete();

			if ( ! cartRequestObserved || pendingRequests.size > 0 ) {
				const timeout = new Promise< never >( ( _, reject ) => {
					deadline = setTimeout( () => {
						reject(
							new Error(
								`Timed out after ${ CART_REQUEST_TIMEOUT }ms waiting for a cart write request to settle.`
							)
						);
					}, CART_REQUEST_TIMEOUT );
				} );

				await Promise.race( [ cartRequestsCompleted, timeout ] );
			}
		} finally {
			if ( deadline !== undefined ) {
				clearTimeout( deadline );
			}
			this.page.off( 'request', requestHandler );
			this.page.off( 'requestfinished', requestSettledHandler );
			this.page.off( 'requestfailed', requestSettledHandler );
		}
	}

	async addToCart( itemName = '' ) {
		await this.performCartAction( async () => {
			if ( itemName !== '' ) {
				// We can't use `getByRole()` here because the Add to Cart button
				// might be a button (in blocks) or a link (in the legacy template).
				await this.page
					.getByLabel( `Add to cart: “${ itemName }”` )
					.click();
			} else {
				await this.page.click( 'text=Add to cart' );
			}
		} );
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
		// Navigate to cart page
		await this.goToCart();

		// Check if cart is already empty
		const emptyCartMessage = this.page.getByText(
			'Your cart is currently empty!'
		);
		if ( await emptyCartMessage.isVisible() ) {
			return; // Cart is already empty
		}

		// Count initial remove buttons and remove all items
		const removeButtons = this.page.getByLabel( /Remove .* from cart/ );
		let itemCount = await removeButtons.count();

		while ( itemCount > 0 ) {
			await this.performCartAction( () => removeButtons.first().click() );

			// Check if empty cart message is now visible
			if ( await emptyCartMessage.isVisible() ) {
				break; // Cart is now empty
			}

			// Update count for next iteration
			itemCount = await removeButtons.count();
		}

		// Final verification that cart is empty
		await emptyCartMessage.waitFor( { state: 'visible' } );
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
