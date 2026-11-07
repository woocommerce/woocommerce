/**
 * External dependencies
 */
import {
	expect,
	test as base,
	guestFile,
	type FrontendUtils,
} from '@woocommerce/e2e-utils';
import type { Page } from '@playwright/test';

/**
 * Internal dependencies
 */
import { CheckoutPage } from '../checkout/checkout.page';
import { SIMPLE_PHYSICAL_PRODUCT_NAME } from '../checkout/constants';

const test = base.extend< { checkoutPageObject: CheckoutPage } >( {
	checkoutPageObject: async ( { page, requestUtils }, provide ) => {
		const pageObject = new CheckoutPage( {
			page,
			requestUtils,
		} );
		await provide( pageObject );
	},
} );

declare global {
	interface Window {
		wcMotionLog: string[];
	}
}

// Logs motion that starts in the Cart and Checkout blocks, except looping animations such as the spinner,
// which keep running under reduced motion on purpose.
const recordMotion = async ( page: Page ) => {
	await page.addInitScript( () => {
		window.wcMotionLog = [];
		const isInBlocks = ( target: EventTarget | null ) =>
			target instanceof Element &&
			!! target.closest(
				'.wp-block-woocommerce-cart, .wp-block-woocommerce-checkout'
			);
		document.addEventListener(
			'animationstart',
			( event ) => {
				const isLooping = ( event.target as Element )
					.getAnimations()
					.some(
						( animation ) =>
							( animation as CSSAnimation ).animationName ===
								event.animationName &&
							animation.effect?.getComputedTiming().iterations ===
								Infinity
					);
				if ( isInBlocks( event.target ) && ! isLooping ) {
					window.wcMotionLog.push(
						`animation ${ event.animationName }`
					);
				}
			},
			true
		);
		document.addEventListener(
			'transitionrun',
			( event ) => {
				if ( isInBlocks( event.target ) ) {
					window.wcMotionLog.push(
						`transition ${ event.propertyName }`
					);
				}
			},
			true
		);
		const startViewTransition =
			document.startViewTransition?.bind( document );
		if ( startViewTransition ) {
			document.startViewTransition = ( ...args ) => {
				window.wcMotionLog.push( 'view transition' );
				return startViewTransition( ...args );
			};
		}
	} );
};

const getMotionLog = async ( page: Page ) => {
	// Animations start on the next frame after the change that triggers them.
	await page.evaluate( () => {
		const { promise, resolve } = Promise.withResolvers< void >();
		requestAnimationFrame( () => requestAnimationFrame( () => resolve() ) );
		return promise;
	} );
	return page.evaluate( () => window.wcMotionLog );
};

// Opens and closes the Cart coupon form, then toggles the Checkout billing address. Returns what moved on each page.
const openAndCloseCartAndCheckoutSections = async (
	page: Page,
	frontendUtils: FrontendUtils,
	checkoutPageObject: CheckoutPage
) => {
	await frontendUtils.goToCart();
	const couponToggle = page.getByRole( 'button', { name: 'Add coupons' } );
	await couponToggle.click();
	await expect( page.getByLabel( 'Enter code' ) ).toBeVisible();
	await couponToggle.click();
	await expect( page.getByLabel( 'Enter code' ) ).toBeHidden();
	await expect( couponToggle ).toHaveAttribute( 'aria-expanded', 'false' );
	const cart = await getMotionLog( page );

	await frontendUtils.goToCheckout();
	await checkoutPageObject.fillInCheckoutWithTestData();
	const billingAddress = page.getByRole( 'group', {
		name: 'Billing address',
	} );
	await checkoutPageObject.unsyncBillingWithShipping();
	await expect( billingAddress ).toBeVisible();
	await checkoutPageObject.fillInCheckoutWithTestData();
	await checkoutPageObject.syncBillingWithShipping();
	await expect( billingAddress ).toBeHidden();
	const checkout = await getMotionLog( page );

	return { cart, checkout };
};

test.describe( 'Shopper → Cart and Checkout motion', () => {
	test.use( { storageState: guestFile } );

	test.beforeEach( async ( { page, frontendUtils } ) => {
		await recordMotion( page );
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( SIMPLE_PHYSICAL_PRODUCT_NAME );
	} );

	test.describe( 'when the shopper allows motion', () => {
		test.use( { contextOptions: { reducedMotion: 'no-preference' } } );

		test( 'Sections finish animating, stay usable, and the order can be placed', async ( {
			page,
			frontendUtils,
			checkoutPageObject,
		} ) => {
			const motion = await openAndCloseCartAndCheckoutSections(
				page,
				frontendUtils,
				checkoutPageObject
			);

			// Confirms the log sees motion, so the reduced motion test below can't pass by logging nothing.
			expect( motion.cart ).not.toEqual( [] );
			expect( motion.checkout ).toContain( 'view transition' );

			await checkoutPageObject.placeOrder();
			await expect(
				page.getByText( 'Thank you. Your order has been received.' )
			).toBeVisible();
		} );
	} );

	test( 'Nothing animates when the shopper prefers reduced motion', async ( {
		page,
		frontendUtils,
		checkoutPageObject,
	} ) => {
		const motion = await openAndCloseCartAndCheckoutSections(
			page,
			frontendUtils,
			checkoutPageObject
		);

		expect( motion ).toEqual( { cart: [], checkout: [] } );
	} );
} );
