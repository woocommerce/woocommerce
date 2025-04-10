/* eslint-disable @woocommerce/dependency-group, jest/expect-expect, jest/no-test-callback, array-callback-return, jest/no-identical-title */

/**
 * WordPress dependencies
 */
import { test, Metrics } from '@wordpress/e2e-test-utils-playwright';

/**
 * Internal dependencies
 */
import { getTotalBlockingTime, median } from '../utils';
import { toggleBlockProductEditor } from '../../e2e-pw/utils/simple-products';

// See https://github.com/WordPress/gutenberg/issues/51383#issuecomment-1613460429
const BROWSER_IDLE_WAIT = 1000;
const NEW_EDITOR_ADD_PRODUCT_URL =
	'wp-admin/admin.php?page=wc-admin&path=%2Fadd-product';

const results = {
	totalBlockingTime: [],
	cumulativeLayoutShift: [],
	largestContentfulPaint: [],
	type: [],
};

test.describe( 'Product editor performance', () => {
	test.use( {
		metrics: async ( { page }, use ) => {
			await use( new Metrics( { page } ) );
		},
	} );

	test.afterAll( async ( {}, testInfo ) => {
		const medians = {};
		Object.keys( results ).forEach( ( metric ) => {
			medians[ metric ] = median( results[ metric ] );
		} );
		await testInfo.attach( 'results', {
			body: JSON.stringify( { 'product-editor': medians }, null, 2 ),
			contentType: 'application/json',
		} );
	} );

	test( 'Enable Product Editor', async ( { page } ) => {
		await toggleBlockProductEditor( 'enable', page );
	} );

	test.describe( 'Loading', () => {
		const samples = 2;
		const throwaway = 1;
		const iterations = samples + throwaway;
		for ( let i = 1; i <= iterations; i++ ) {
			test( `Run the test (${ i } of ${ iterations })`, async ( {
				page,
				metrics,
			} ) => {
				await page.goto( NEW_EDITOR_ADD_PRODUCT_URL );

				// Wait for the Preview button.
				await page
					.locator( '[aria-label="Preview in new tab"]' )
					.first()
					.waitFor();

				// Get the durations.
				const loadingDurations = await metrics.getLoadingDurations();

				// Measure CLS
				const cumulativeLayoutShift =
					await metrics.getCumulativeLayoutShift();

				// Measure LCP
				const largestContentfulPaint =
					await metrics.getLargestContentfulPaint();

				// Measure TBT
				const totalBlockingTime = await getTotalBlockingTime(
					page,
					BROWSER_IDLE_WAIT
				);

				// Save the results.
				if ( i > throwaway ) {
					Object.entries( loadingDurations ).forEach(
						( [ metric, duration ] ) => {
							const metricKey =
								metric === 'timeSinceResponseEnd'
									? 'firstBlock'
									: metric;
							if ( ! results[ metricKey ] ) {
								results[ metricKey ] = [];
							}
							results[ metricKey ].push( duration );
						}
					);
					results.totalBlockingTime = results.tbt || [];
					results.totalBlockingTime.push( totalBlockingTime );
					results.cumulativeLayoutShift =
						results.cumulativeLayoutShift || [];
					results.cumulativeLayoutShift.push( cumulativeLayoutShift );
					results.largestContentfulPaint =
						results.largestContentfulPaint || [];
					results.largestContentfulPaint.push(
						largestContentfulPaint
					);
				}
			} );
		}
	} );

	test.describe( 'Typing', () => {
		test( 'Run the test', async ( { page, metrics } ) => {
			await page.goto( NEW_EDITOR_ADD_PRODUCT_URL );

			// Wait for the Preview button.
			await page
				.locator( '[aria-label="Preview in new tab"]' )
				.first()
				.waitFor();

			// FTUX tour on first run through
			try {
				await page
					.getByLabel( 'Close Tour' )
					.click( { timeout: 3000 } );
			} catch ( e ) {
				console.log( 'Tour was not visible, skipping.' );
			}

			const input = page.getByPlaceholder( 'e.g. 12 oz Coffee Mug' );

			// The first character typed triggers a longer time (isTyping change).
			// It can impact the stability of the metric, so we exclude it. It
			// probably deserves a dedicated metric itself, though.
			const samples = 10;
			const throwaway = 1;
			const iterations = samples + throwaway;

			// Start tracing.
			await metrics.startTracing();

			// Type the testing sequence into the empty input.
			await input.type( 'x'.repeat( iterations ), {
				delay: BROWSER_IDLE_WAIT,
				// The extended timeout is needed because the typing is very slow
				// and the `delay` value itself does not extend it.
				timeout: iterations * BROWSER_IDLE_WAIT * 2, // 2x the total time to be safe.
			} );

			// Stop tracing.
			await metrics.stopTracing();

			// Get the durations.
			const [ keyDownEvents, keyPressEvents, keyUpEvents ] =
				metrics.getTypingEventDurations();

			// Save the results.
			results.type = [];
			for ( let i = throwaway; i < iterations; i++ ) {
				results.type.push(
					keyDownEvents[ i ] + keyPressEvents[ i ] + keyUpEvents[ i ]
				);
			}
		} );
	} );
} );

/* eslint-enable @woocommerce/dependency-group, jest/expect-expect, jest/no-test-callback, array-callback-return, jest/no-identical-title */
