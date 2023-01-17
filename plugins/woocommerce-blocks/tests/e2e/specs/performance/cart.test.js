/**
 * Internal dependencies
 */
import { shopper, getLoadingDurations } from '../../../utils';
import { SIMPLE_PHYSICAL_PRODUCT_NAME } from '../../../utils/constants';
import { logPerformanceResult } from '../../utils';

describe( 'Cart performance', () => {
	beforeAll( async () => {
		await shopper.goToShop();
		await shopper.addToCartFromShopPage( SIMPLE_PHYSICAL_PRODUCT_NAME );
	} );

	it( 'Loading', async () => {
		await shopper.block.goToCart();

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

		// Measuring loading time.
		while ( i-- ) {
			await page.reload();
			await page.waitForSelector( '.wc-block-cart' );
			const {
				serverResponse,
				firstPaint,
				domContentLoaded,
				loaded,
				firstContentfulPaint,
				firstBlock,
			} = await getLoadingDurations();

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
			logPerformanceResult( `Cart block loading: (${ name })`, value );
		} );

		// To stop warning about no assertions.
		expect( true ).toBe( true );
	} );

	it( 'Quantity change', async () => {
		await shopper.block.goToCart();
		await page.waitForSelector(
			'button.wc-block-components-quantity-selector__button--plus'
		);
		let i = 3;

		const timesForResponse = [];
		while ( i-- ) {
			const start = performance.now();
			await expect( page ).toClick(
				'button.wc-block-components-quantity-selector__button--plus'
			);
			await page.waitForResponse(
				( response ) =>
					response.url().indexOf( '/wc/store/v1/batch' ) !== -1 &&
					response.status() === 207
			);
			const end = performance.now();
			timesForResponse.push( end - start );
		}
		logPerformanceResult(
			'Cart block: Change cart item quantity',
			timesForResponse
		);
	} );

	it( 'Coupon entry', async () => {
		await shopper.block.goToCart();
		await page.waitForSelector(
			'button.wc-block-components-quantity-selector__button--plus'
		);
		let i = 3;
		await expect( page ).toClick(
			'.wc-block-components-totals-coupon-link'
		);
		const timesForResponse = [];
		while ( i-- ) {
			const start = performance.now();
			await expect( page ).toFill(
				'[aria-label="Enter code"]',
				'test_coupon'
			);
			await expect( page ).toClick( 'button', { text: 'Apply' } );
			await page.waitForResponse(
				( response ) =>
					response.url().indexOf( '/wc/store/v1/batch' ) !== -1 &&
					response.status() === 207
			);
			const end = performance.now();

			timesForResponse.push( end - start );
		}
		logPerformanceResult( 'Cart block: Coupon entry', timesForResponse );
	} );
} );
