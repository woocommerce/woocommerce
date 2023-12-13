/**
 * External dependencies
 */
import { test as base, expect } from '@woocommerce/e2e-playwright-utils';
import {
	installPluginFromPHPFile,
	uninstallPluginFromPHPFile,
} from '@woocommerce/e2e-mocks/custom-plugins';

/**
 * Internal dependencies
 */
import { CartPage } from './cart.page';
import {
	DISCOUNTED_PRODUCT_NAME,
	REGULAR_PRICED_PRODUCT_NAME,
	SIMPLE_PHYSICAL_PRODUCT_NAME,
} from '../checkout/constants';

const test = base.extend< { pageObject: CartPage } >( {
	pageObject: async ( { page }, use ) => {
		const pageObject = new CartPage( {
			page,
		} );
		await use( pageObject );
	},
} );

test.describe( 'Cart performance', () => {
	test.beforeAll( async ( { frontendUtils } ) => {
		await frontendUtils.goToShop();
		await frontendUtils.emptyCart();
		await frontendUtils.addToCart( SIMPLE_PHYSICAL_PRODUCT_NAME );
	} );

	test( 'Loading', async ( {
		pageObject,
		frontendUtils,
		page,
		performanceUtils,
	} ) => {
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

		// Measuring loading time.
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
			logPerformanceResult( `Cart block loading: (${ name })`, value );
		} );

		// To stop warning about no assertions.
		expect( true ).toBe( true );
	} );
} );
