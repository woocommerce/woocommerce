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

// Logs movement that starts in the Cart and Checkout blocks. Fades and colour changes don't move anything, so they
// aren't logged: they still play under reduced motion.
const recordMotion = async ( page: Page ) => {
	await page.addInitScript( () => {
		window.wcMotionLog = [];
		const fadeAnimations = [
			'wc-fade-in',
			'wc-fade-out',
			'wc-fade-text-out',
			'wc-hold-visible',
		];
		const fadeProperties = [
			'opacity',
			'visibility',
			'color',
			'background-color',
			'border-color',
			'border-top-color',
			'border-right-color',
			'border-bottom-color',
			'border-left-color',
			'outline-color',
			'box-shadow',
			'fill',
			'stroke',
			'text-decoration-color',
			'-webkit-text-fill-color',
		];
		const isInBlocks = ( target: EventTarget | null ) =>
			target instanceof Element &&
			!! target.closest(
				'.wp-block-woocommerce-cart, .wp-block-woocommerce-checkout'
			);
		document.addEventListener(
			'animationstart',
			( event ) => {
				if (
					isInBlocks( event.target ) &&
					! fadeAnimations.includes( event.animationName )
				) {
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
				if (
					isInBlocks( event.target ) &&
					! fadeProperties.includes( event.propertyName )
				) {
					window.wcMotionLog.push(
						`transition ${ event.propertyName }`
					);
				}
			},
			true
		);
		// Layout changes, such as a section growing in, run as script animations of its size.
		const animate = Element.prototype.animate;
		Element.prototype.animate = function ( keyframes, options ) {
			const properties = (
				Array.isArray( keyframes ) ? keyframes : [ keyframes ?? {} ]
			).flatMap( ( keyframe ) => Object.keys( keyframe ?? {} ) );
			const moves = properties.filter(
				( property ) =>
					! [ 'offset', 'easing', 'composite', 'opacity' ].includes(
						property
					)
			);
			if ( isInBlocks( this ) && moves.length ) {
				window.wcMotionLog.push(
					`script ${ [ ...new Set( moves ) ].join( ',' ) }`
				);
			}
			return animate.call( this, keyframes, options );
		};
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
			expect( motion.checkout ).not.toEqual( [] );

			await checkoutPageObject.placeOrder();
			await expect(
				page.getByText( 'Thank you. Your order has been received.' )
			).toBeVisible();
		} );
	} );

	test( 'Nothing moves when the shopper prefers reduced motion', async ( {
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
