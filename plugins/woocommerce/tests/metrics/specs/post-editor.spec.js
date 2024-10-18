/* eslint-disable @woocommerce/dependency-group, jest/expect-expect, jest/no-test-callback, array-callback-return, jest/no-identical-title */

/**
 * WordPress dependencies
 */
import { test as base, Metrics } from '@wordpress/e2e-test-utils-playwright';

/**
 * Internal dependencies
 */
import { PerfUtils } from '../fixtures';
import { getTotalBlockingTime, median } from '../utils';

// See https://github.com/WordPress/gutenberg/issues/51383#issuecomment-1613460429
const BROWSER_IDLE_WAIT = 1000;

const results = {};

const test = base.extend( {
	perfUtils: async ( { page }, use ) => {
		await use( new PerfUtils( { page } ) );
	},
	metrics: async ( { page }, use ) => {
		await use( new Metrics( { page } ) );
	},
} );

let draftId = null;

test( 'Setup the test post', async ( { admin, editor, perfUtils } ) => {
	await admin.createNewPost();

	const blocks = [
		{
			name: 'woocommerce/all-products',
		},
		{
			name: 'woocommerce/all-reviews',
		},
		{
			name: 'woocommerce/featured-category',
			setup: async ( block ) => {
				await block
					.getByRole( 'radio', { name: /Accessories/ } )
					.check();
				await block.getByRole( 'button', { name: 'Done' } ).click();
			},
		},
		{
			name: 'woocommerce/featured-product',
			setup: async ( block ) => {
				await block
					.getByLabel( 'Beanie with Logo', { exact: true } )
					.check();
				await block.getByRole( 'button', { name: 'Done' } ).click();
			},
		},
		{
			name: 'woocommerce/product-best-sellers',
		},
		{
			name: 'woocommerce/product-category',
			setup: async ( block ) => {
				await block.getByLabel( 'Clothing' ).check();
				await block.getByRole( 'button', { name: 'Done' } ).click();
			},
		},
		{
			name: 'woocommerce/product-collection',
			setup: async ( block ) => {
				await block
					.getByRole( 'button', { name: 'create your own' } )
					.click();
			},
		},
		{
			name: 'woocommerce/product-new',
		},
		{
			name: 'woocommerce/product-on-sale',
		},
		{
			name: 'woocommerce/product-tag',
			setup: async ( block ) => {
				await block.getByLabel( 'Recommended' ).check();
				await block.getByRole( 'button', { name: 'Done' } ).click();
			},
		},
		{
			name: 'woocommerce/product-top-rated',
		},
		{
			name: 'woocommerce/products-by-attribute',
			setup: async ( block ) => {
				await block.getByLabel( 'Color' ).check();
				await block.getByRole( 'button', { name: 'Done' } ).click();
			},
		},
		{
			name: 'woocommerce/single-product',
			setup: async ( block ) => {
				await block.getByLabel( 'Album', { exact: true } ).check();
				await block.getByRole( 'button', { name: 'Done' } ).click();
			},
		},
		{
			name: 'woocommerce/cart',
		},
		{
			name: 'woocommerce/checkout',
		},
	];

	for ( const block of blocks ) {
		await editor.insertBlock( {
			name: 'core/heading',
			attributes: { level: 3, content: block.name },
		} );

		await editor.insertBlock( { name: block.name } );

		if ( block.setup ) {
			await block.setup(
				editor.canvas.locator( `[data-type="${ block.name }"]` )
			);
		}
	}

	draftId = await perfUtils.saveDraft();
} );

test.describe( 'Post Editor Performance', () => {
	test.afterAll( async ( {}, testInfo ) => {
		const medians = {};
		Object.keys( results ).forEach( ( metric ) => {
			medians[ metric ] = median( results[ metric ] );
		} );
		await testInfo.attach( 'results', {
			body: JSON.stringify( { editor: medians }, null, 2 ),
			contentType: 'application/json',
		} );
	} );

	test.describe( 'Loading', () => {
		const samples = 3;
		const throwaway = 1;
		const iterations = samples + throwaway;
		for ( let i = 1; i <= iterations; i++ ) {
			test( `Run the test (${ i } of ${ iterations })`, async ( {
				admin,
				page,
				perfUtils,
				metrics,
			} ) => {
				// Open the test draft.
				await admin.editPost( draftId );
				const canvas = await perfUtils.getCanvas();

				// Wait for the first block.
				await canvas.locator( '.wp-block' ).first().waitFor();

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
				}
			} );
		}
	} );

	test.describe( 'Typing', () => {
		test( 'Run the test', async ( {
			admin,
			editor,
			perfUtils,
			metrics,
		} ) => {
			await admin.editPost( draftId );
			await perfUtils.disableAutosave();
			const canvas = await perfUtils.getCanvas();

			await editor.insertBlock( { name: 'core/paragraph' } );
			const paragraph = canvas.getByRole( 'document', {
				name: /Empty block/i,
			} );

			// The first character typed triggers a longer time (isTyping change).
			// It can impact the stability of the metric, so we exclude it. It
			// probably deserves a dedicated metric itself, though.
			const samples = 10;
			const throwaway = 1;
			const iterations = samples + throwaway;

			// Start tracing.
			await metrics.startTracing();

			// Type the testing sequence into the empty paragraph.
			await paragraph.type( 'x'.repeat( iterations ), {
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
