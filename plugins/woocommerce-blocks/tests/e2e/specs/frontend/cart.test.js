/**
 * External dependencies
 */
import { switchUserToAdmin } from '@wordpress/e2e-test-utils';
import { shopper } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import {
	getBlockPagePermalink,
	getNormalPagePermalink,
	visitPostOfType,
	scrollTo,
} from '../../../utils';

const block = {
	name: 'Cart',
	slug: 'woocommerce/cart',
	class: '.wc-block-cart',
};

if ( process.env.WOOCOMMERCE_BLOCKS_PHASE < 2 )
	// eslint-disable-next-line jest/no-focused-tests
	test.only( `skipping ${ block.name } tests`, () => {} );

describe( `${ block.name } Block (frontend)`, () => {
	let cartBlockPermalink;
	let productPermalink;

	beforeAll( async () => {
		await page.evaluate( () => window.localStorage.clear() );
		await page.evaluate( () => {
			localStorage.setItem(
				'wc-blocks_dismissed_compatibility_notices',
				'["checkout","cart"]'
			);
		} );
		await switchUserToAdmin();
		cartBlockPermalink = await getBlockPagePermalink(
			`${ block.name } Block`
		);
		await visitPostOfType( 'Woo Single #1', 'product' );
		productPermalink = await getNormalPagePermalink();
		await page.goto( productPermalink );
		await shopper.addToCart();
	} );
	afterAll( async () => {
		// empty cart from shortcode page
		await shopper.goToCart();
		await shopper.removeFromCart( 'Woo Single #1' );
		await page.evaluate( () => {
			localStorage.removeItem(
				'wc-blocks_dismissed_compatibility_notices'
			);
		} );
	} );

	it( 'Adds a timestamp to localstorage when the cart is updated', async () => {
		await jest.setTimeout( 60000 );
		await page.goto( cartBlockPermalink );
		await page.waitForFunction( () => {
			const wcCartStore = wp.data.select( 'wc/store/cart' );
			return (
				! wcCartStore.isResolving( 'getCartData' ) &&
				wcCartStore.hasFinishedResolution( 'getCartData', [] )
			);
		} );
		await page.click(
			'.wc-block-cart__main .wc-block-components-quantity-selector__button--plus'
		);
		await page.waitForFunction( () => {
			const timeStamp = window.localStorage.getItem(
				'wc-blocks_cart_update_timestamp'
			);
			return typeof timeStamp === 'string' && timeStamp;
		} );
		const timestamp = await page.evaluate( () => {
			return window.localStorage.getItem(
				'wc-blocks_cart_update_timestamp'
			);
		} );
		expect( timestamp ).not.toBeNull();
	} );

	it( 'Shows the freshest cart data when using browser navigation buttons', async () => {
		await page.goto( cartBlockPermalink );

		await page
			.waitForFunction( () => {
				const wcCartStore = wp.data.select( 'wc/store/cart' );
				return (
					! wcCartStore.isResolving( 'getCartData' ) &&
					wcCartStore.hasFinishedResolution( 'getCartData', [] )
				);
			} )
			.catch( ( err ) => {
				throw new Error(
					'Waiting for the wc/store/cart to not be resolving and to have finished resolution failed. This probably means the block did not load correctly.'
				);
			} );
		await page.click(
			'.wc-block-cart__main .wc-block-components-quantity-selector__button--plus'
		);

		await page.waitForResponse(
			( response ) =>
				( response.url().includes( '/wc/store/cart/update-item' ) &&
					response.status() === 200 ) ||
				( response.url().includes( '/wc/store/batch' ) &&
					response.status() === 207 )
		);

		const selectedValue = parseInt(
			await page.$eval(
				'.wc-block-cart__main .wc-block-components-quantity-selector__input',
				( el ) => el.value
			)
		);

		// This is to ensure we've clicked the right cart button.
		expect( selectedValue ).toBeGreaterThan( 1 );

		await scrollTo( '.wc-block-cart__submit-button' );
		await Promise.all( [
			page.waitForNavigation(),
			page.click( '.wc-block-cart__submit-button' ),
		] );

		// go to checkout page
		// note: shortcode checkout on CI / block on local env
		await page.waitForSelector( 'h1', { text: 'Checkout' } );

		// navigate back to cart block page
		await page.goBack( { waitUntil: 'networkidle0' } );

		await page
			.waitForFunction(
				() => {
					const timeStamp = window.localStorage.getItem(
						'wc-blocks_cart_update_timestamp'
					);
					return typeof timeStamp === 'string' && timeStamp;
				},
				{ timeout: 5000 }
			)
			.catch( ( err ) => {
				throw new Error(
					'Could not get the wc-blocks_cart_update_timestamp item from local storage. It must not have been set by the cartUpdateMiddleware.'
				);
			} );

		const timestamp = await page.evaluate( () => {
			return window.localStorage.getItem(
				'wc-blocks_cart_update_timestamp'
			);
		} );
		expect( timestamp ).not.toBeNull();

		// We need this to check if the block is done loading.
		await page
			.waitForFunction( () => {
				const wcCartStore = wp.data.select( 'wc/store/cart' );
				return (
					! wcCartStore.isResolving( 'getCartData' ) &&
					wcCartStore.hasFinishedResolution( 'getCartData', [] )
				);
			} )
			.catch( ( err ) => {
				throw new Error(
					'Waiting for the wc/store/cart to not be resolving and to have finished resolution failed. This probably means the block did not load correctly.'
				);
			} );

		// Then we check to ensure the stale cart action has been emitted, so it'll fetch the cart from the API.
		await page
			.waitForFunction( () => {
				const wcCartStore = wp.data.select( 'wc/store/cart' );
				return wcCartStore.isCartDataStale() === true;
			} )
			.catch( ( err ) => {
				throw new Error(
					'isCartDataStale on the wc/store/cart store is not true. The cart contents were not correctly marked as stale.'
				);
			} );

		const value = parseInt(
			await page.$eval(
				'.wc-block-components-quantity-selector__input',
				( el ) => el.value
			)
		);
		expect( value ).toBe( selectedValue );
	} );
} );
