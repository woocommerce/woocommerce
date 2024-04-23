/**
 * External dependencies
 */
import { test as base, expect } from '@woocommerce/e2e-playwright-utils';

/**
 * Internal dependencies
 */
import { CartPage } from './cart.page';
import { SIMPLE_PHYSICAL_PRODUCT_NAME } from '../checkout/constants';

const test = base.extend< { pageObject: CartPage } >( {
	pageObject: async ( { page }, use ) => {
		const pageObject = new CartPage( {
			page,
		} );
		await use( pageObject );
	},
} );

test.describe( 'Cart performance', () => {
	test.beforeEach( async ( { frontendUtils } ) => {
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( SIMPLE_PHYSICAL_PRODUCT_NAME );
	} );

	test( 'Loading', async ( { frontendUtils, page, performanceUtils } ) => {
		await frontendUtils.goToCart();

		const results = {
			serverResponse: [],
			firstPaint: [],
			domContentLoaded: [],
			loaded: [],
			firstContentfulPaint: [],
			firstBlock: [],
			type: [],
			focus: [],
			inserterOpen: [],
			inserterHover: [],
			inserterSearch: [],
			listViewOpen: [],
		};

		let i = 3;
		while ( i-- ) {
			await page.reload();
			await page.locator( '.wc-block-cart' ).waitFor();
			const {
				serverResponse,
				firstPaint,
				domContentLoaded,
				loaded,
				firstContentfulPaint,
				firstBlock,
			} = await performanceUtils.getLoadingDurations();
			// Multiply by 1000 to get time in ms
			results.serverResponse.push( serverResponse * 1000 );
			results.firstPaint.push( firstPaint * 1000 );
			results.domContentLoaded.push( domContentLoaded * 1000 );
			results.loaded.push( loaded * 1000 );
			results.firstContentfulPaint.push( firstContentfulPaint * 1000 );
			results.firstBlock.push( firstBlock * 1000 );
		}

		Object.entries( results ).forEach( ( [ name, value ] ) => {
			if (
				Array.isArray( value ) &&
				value.every( ( x ) => typeof x === 'number' ) &&
				value.length === 0
			) {
				return;
			}
			performanceUtils.logPerformanceResult(
				`Cart block loading: (${ name })`,
				value
			);
		} );

		expect( true ).toBe( true );
	} );

	test( 'Quantity change', async ( {
		frontendUtils,
		page,
		performanceUtils,
	} ) => {
		await frontendUtils.goToCart();
		await page
			.locator(
				'button.wc-block-components-quantity-selector__button--plus'
			)
			.waitFor();

		const timesForResponse = [];
		let i = 3;
		while ( i-- ) {
			const start = performance.now();

			const responsePromise = page.waitForResponse(
				( responseFromWait ) =>
					responseFromWait.url().includes( '/wc/store/v1/batch' ) &&
					responseFromWait.status() === 207
			);

			await page.click(
				'button.wc-block-components-quantity-selector__button--plus'
			);

			const response = await responsePromise;

			expect( response.ok() ).toBeTruthy();

			const end = performance.now();
			timesForResponse.push( end - start );
		}

		performanceUtils.logPerformanceResult(
			'Cart block: Change cart item quantity',
			timesForResponse
		);
	} );
	test( 'Coupon entry', async ( {
		frontendUtils,
		page,
		performanceUtils,
	} ) => {
		await frontendUtils.goToCart();
		await page
			.locator(
				'button.wc-block-components-quantity-selector__button--plus'
			)
			.waitFor();
		await page.click( '.wc-block-components-totals-coupon-link' );

		const timesForResponse = [];
		let i = 3;
		while ( i-- ) {
			const start = performance.now();

			await page.fill( '[aria-label="Enter code"]', 'test_coupon' );

			const responsePromise = page.waitForResponse(
				( responseFromWait ) =>
					responseFromWait.url().includes( '/wc/store/v1/batch' ) &&
					responseFromWait.status() === 207
			);

			await page.click(
				'button.wc-block-components-totals-coupon__button'
			);

			const response = await responsePromise;

			expect( response.ok() ).toBeTruthy();

			const end = performance.now();
			timesForResponse.push( end - start );
		}

		performanceUtils.logPerformanceResult(
			'Cart block: Coupon entry',
			timesForResponse
		);
	} );
} );
