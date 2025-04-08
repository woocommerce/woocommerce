/* eslint-disable @woocommerce/dependency-group, jest/expect-expect, jest/no-test-callback, array-callback-return, jest/no-identical-title */

/**
 * WordPress dependencies
 */
import { test, Metrics } from '@wordpress/e2e-test-utils-playwright';

/**
 * Internal dependencies
 */
import { getTotalBlockingTime, median } from '../utils';

// See https://github.com/WordPress/gutenberg/issues/51383#issuecomment-1613460429
const BROWSER_IDLE_WAIT = 1000;
const HOME = '/';

const results = {
	totalBlockingTime: [],
	largestContentfulPaint: [],
	type: [],
};

test.describe( 'Frontend Performance', () => {
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
			body: JSON.stringify( { frontend: medians }, null, 2 ),
			contentType: 'application/json',
		} );
	} );

	test.describe( 'Loading', () => {
		const samples = 10;
		const throwaway = 1;
		const iterations = samples + throwaway;
		for ( let i = 1; i <= iterations; i++ ) {
			test( `Run the test (${ i } of ${ iterations })`, async ( {
				page,
				metrics,
			} ) => {
				await page.goto( HOME );

				// Wait for the site title to load.
				await page.locator( '[aria-current="page"]' ).first().waitFor();

				// Get the durations.
				const loadingDurations = await metrics.getLoadingDurations();

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
					results.largestContentfulPaint =
						results.largestContentfulPaint || [];
					results.largestContentfulPaint.push(
						largestContentfulPaint
					);
				}
			} );
		}
	} );
} );

/* eslint-enable @woocommerce/dependency-group, jest/expect-expect, jest/no-test-callback, array-callback-return, jest/no-identical-title */
