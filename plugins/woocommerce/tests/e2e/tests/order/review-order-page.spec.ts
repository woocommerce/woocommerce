/**
 * E2E coverage for the Customer Review Request → Review Order page.
 * Products/orders seeded via REST for speed; everything else via UI.
 * Tracked as WOOPLUG-6601 (Linear).
 */

/**
 * External dependencies
 */
import { request, type Locator } from '@playwright/test';
import {
	ApiClient,
	WC_API_PATH,
	WP_API_PATH,
} from '@woocommerce/e2e-utils-playwright';

/**
 * Internal dependencies
 */
import { tags, expect, test } from '../../fixtures/fixtures';
import { setOption, deleteOption } from '../../utils/options';
import { random } from '../../utils/helpers';
import { wpCLI } from '../../utils/cli';

type SeededOrder = {
	id: number;
	key: string;
};

// `page` is logged-out (Review Order is guest-accessible via order key);
// `restApi` is admin basic-auth.

const FEATURE_FLAG_OPTION =
	'woocommerce_feature_customer_review_request_enabled';

test.describe(
	'Customer Review Request — Review Order page',
	{ tag: [ tags.SERVICES, tags.HPOS ] },
	() => {
		// Host page permalink. The page is created by the OrderReviews
		// Endpoint on `init` after the feature flag is enabled; we look it up
		// once in beforeAll and reuse it for every URL we build.
		let hostPagePermalink = '';

		test.beforeAll( async ( { baseURL, restApi } ) => {
			await setOption(
				request,
				baseURL || '',
				FEATURE_FLAG_OPTION,
				'yes'
			);

			// Drop the stored host-page id so the next request re-adopts the
			// canonical review-order page and reflushes its rewrite.
			await deleteOption(
				request,
				baseURL || '',
				'woocommerce_review_order_page_id'
			);

			const { data } = await restApi.get(
				`${ WP_API_PATH }/pages?slug=review-order`,
				{ data: { _fields: [ 'id', 'link' ] } }
			);
			hostPagePermalink = data?.[ 0 ]?.link || '';

			// Surface the host-page creation race as a clear setup failure
			// instead of letting every scenario fail with a confusing 404.
			if ( ! hostPagePermalink ) {
				throw new Error(
					'Review Order host page was not created after enabling the feature flag.'
				);
			}
		} );

		test.afterAll( async ( { baseURL } ) => {
			await deleteOption( request, baseURL || '', FEATURE_FLAG_OPTION );
		} );

		// Matches `Endpoint::get_url()` for pretty permalinks (the e2e env
		// runs /%postname%/): /review-order/{id}/?key={key}.
		const reviewOrderUrl = ( order: SeededOrder ): string =>
			`${ hostPagePermalink }${ order.id }/?key=${ order.key }`;

		// Star inputs are CSS-hidden behind their <label>s for a11y; clicking
		// the label triggers the radio without needing { force: true }.
		const rateRow = ( row: Locator, stars: number ) =>
			row
				.locator( 'label.woocommerce-star-rating__star' )
				.filter( {
					hasText: new RegExp( `${ stars } out of 5 stars` ),
				} )
				.click();

		// Cleanup helpers log on failure rather than throw — cleanup runs in
		// `finally`, so throwing here would mask the real test error.
		const cleanupProducts = async ( restApi: ApiClient, ids: number[] ) => {
			for ( const id of ids ) {
				if ( id <= 0 ) {
					continue;
				}
				await restApi
					.delete( `${ WC_API_PATH }/products/${ id }`, {
						force: true,
					} )
					.catch( ( err ) => {
						// eslint-disable-next-line no-console -- surface unexpected teardown errors without masking the test failure.
						console.warn(
							`Failed to delete product ${ id }:`,
							err
						);
					} );
			}
		};

		const cleanupOrder = async ( restApi: ApiClient, id: number ) => {
			if ( id <= 0 ) {
				return;
			}
			await restApi
				.delete( `${ WC_API_PATH }/orders/${ id }`, { force: true } )
				.catch( ( err ) => {
					// eslint-disable-next-line no-console -- surface unexpected teardown errors without masking the test failure.
					console.warn( `Failed to delete order ${ id }:`, err );
				} );
		};

		const buildBillingEmail = ( prefix: string ): string =>
			`${ prefix }+${ random() }@example.test`;

		/**
		 * Create N simple products plus a completed order containing them.
		 * Cleans up its own partial state if any step throws.
		 *
		 * @param restApi        Playwright fixture rest client.
		 * @param productConfigs One config per product. Each can override `reviews_allowed`.
		 * @return Created order id + key + the product ids in input order + the billing email used.
		 */
		const seedCompletedOrder = async (
			restApi: ApiClient,
			productConfigs: Array< {
				name?: string;
				reviews_allowed?: boolean;
			} >
		): Promise< {
			order: SeededOrder;
			productIds: number[];
			billingEmail: string;
		} > => {
			const productIds: number[] = [];
			const billingEmail = buildBillingEmail( 'shopper' );
			let orderId = 0;
			try {
				for ( const cfg of productConfigs ) {
					const { data } = await restApi.post(
						`${ WC_API_PATH }/products`,
						{
							name:
								cfg.name ||
								`Review Order Test Product ${ random() }`,
							type: 'simple',
							regular_price: '10',
							reviews_allowed: cfg.reviews_allowed ?? true,
						}
					);
					productIds.push( data.id );
				}

				const { data: order } = await restApi.post(
					`${ WC_API_PATH }/orders`,
					{
						status: 'completed',
						billing: {
							first_name: 'Review',
							last_name: 'Tester',
							email: billingEmail,
						},
						line_items: productIds.map( ( id ) => ( {
							product_id: id,
							quantity: 1,
						} ) ),
					}
				);
				orderId = order.id;

				return {
					order: { id: order.id, key: order.order_key },
					productIds,
					billingEmail,
				};
			} catch ( err ) {
				// Make sure a partial seed doesn't strand products / orders.
				await cleanupOrder( restApi, orderId );
				await cleanupProducts( restApi, productIds );
				throw err;
			}
		};

		/**
		 * Create a variable product with `variationOptions.length` variations
		 * of a single `Size` attribute, all reviewable, plus a completed order
		 * containing each variation. Cleans up partial state on failure.
		 */
		const seedVariationOrder = async (
			restApi: ApiClient,
			variationOptions: string[]
		): Promise< {
			order: SeededOrder;
			parentId: number;
			variationIds: number[];
			billingEmail: string;
		} > => {
			let parentId = 0;
			let orderId = 0;
			const variationIds: number[] = [];
			const billingEmail = buildBillingEmail( 'variation-shopper' );
			try {
				const { data: parent } = await restApi.post(
					`${ WC_API_PATH }/products`,
					{
						name: `Variable Review Test ${ random() }`,
						type: 'variable',
						attributes: [
							{
								name: 'Size',
								visible: true,
								variation: true,
								options: variationOptions,
							},
						],
					}
				);
				parentId = parent.id;

				for ( const option of variationOptions ) {
					const { data: variation } = await restApi.post(
						`${ WC_API_PATH }/products/${ parent.id }/variations`,
						{
							regular_price: '10',
							attributes: [ { name: 'Size', option } ],
						}
					);
					variationIds.push( variation.id );
				}

				const { data: order } = await restApi.post(
					`${ WC_API_PATH }/orders`,
					{
						status: 'completed',
						billing: {
							first_name: 'Variation',
							last_name: 'Tester',
							email: billingEmail,
						},
						line_items: variationIds.map( ( vid ) => ( {
							product_id: parent.id,
							variation_id: vid,
							quantity: 1,
						} ) ),
					}
				);
				orderId = order.id;

				return {
					order: { id: order.id, key: order.order_key },
					parentId,
					variationIds,
					billingEmail,
				};
			} catch ( err ) {
				await cleanupOrder( restApi, orderId );
				await cleanupProducts( restApi, [ parentId ] );
				throw err;
			}
		};

		test(
			'WP-CLI preserves the pending rewrite flush only when the active theme is skipped',
			{ tag: '@skip-on-external-env' },
			async () => {
				test.skip(
					process.env.DISABLE_HPOS === '1',
					'WP-CLI theme skipping is independent of HPOS and is covered by the core serial projects.'
				);

				// Setup and inspection must not load WooCommerce, or those commands could consume the queue they are measuring.
				const coreOnlyFlags = [ '--skip-plugins', '--skip-themes' ];
				const pendingOption =
					'woocommerce_review_order_flush_rewrite_pending';
				const seedPendingRewrite = () =>
					wpCLI( [
						'wp',
						'eval',
						`update_option( "${ pendingOption }", "yes" );`,
						...coreOnlyFlags,
					] );
				const readPendingRewrite = async () =>
					(
						await wpCLI( [
							'wp',
							'eval',
							`echo get_option( "${ pendingOption }", "missing" );`,
							...coreOnlyFlags,
						] )
					).stdout.trim();
				const unrelatedPlugins = (
					await wpCLI( [
						'wp',
						'plugin',
						'list',
						'--status=active',
						'--field=name',
						...coreOnlyFlags,
					] )
				).stdout
					.split( /\r?\n/ )
					.filter( ( plugin ) => plugin && plugin !== 'woocommerce' );
				const skipUnrelatedPlugins = unrelatedPlugins.length
					? [ `--skip-plugins=${ unrelatedPlugins.join( ',' ) }` ]
					: [];
				const activeTheme = (
					await wpCLI( [
						'wp',
						'option',
						'get',
						'stylesheet',
						...coreOnlyFlags,
					] )
				).stdout.trim();
				const inactiveTheme =
					(
						await wpCLI( [
							'wp',
							'theme',
							'list',
							'--status=inactive',
							'--field=name',
							...coreOnlyFlags,
						] )
					).stdout
						.split( /\r?\n/ )
						.find( Boolean ) ?? '';

				expect(
					inactiveTheme,
					'Expected an inactive theme for the WP-CLI control.'
				).not.toBe( '' );

				try {
					for ( const skipThemes of [
						'--skip-themes',
						`--skip-themes=${ activeTheme }`,
					] ) {
						await seedPendingRewrite();
						await wpCLI( [
							'wp',
							'option',
							'get',
							'siteurl',
							skipThemes,
							...skipUnrelatedPlugins,
						] );
						expect(
							await readPendingRewrite(),
							`Expected the queue to survive ${ skipThemes }.`
						).toBe( 'yes' );
					}

					await seedPendingRewrite();
					await wpCLI( [
						'wp',
						'option',
						'get',
						'siteurl',
						`--skip-themes=${ inactiveTheme }`,
						...skipUnrelatedPlugins,
					] );
					expect(
						await readPendingRewrite(),
						'Expected the inactive-theme control to consume the queue.'
					).toBe( 'missing' );
				} finally {
					await wpCLI( [
						'wp',
						'eval',
						`delete_option( "${ pendingOption }" );`,
						...coreOnlyFlags,
					] );
					// Let the next normal request regenerate the complete ruleset without loading extension-heavy state under WP-CLI.
					await wpCLI( [
						'wp',
						'option',
						'update',
						'rewrite_rules',
						'',
						...coreOnlyFlags,
					] );
				}
			}
		);

		test( 'Scenario 1 — happy path: rate a product, submit, see thank-you in place', async ( {
			page,
			restApi,
		} ) => {
			const { order, productIds } = await seedCompletedOrder( restApi, [
				{ name: 'CRR Product A' },
				{ name: 'CRR Product B' },
			] );

			try {
				await page.goto( reviewOrderUrl( order ) );

				await expect(
					page.getByRole( 'heading', {
						name: 'Review your order',
					} )
				).toBeVisible();
				await expect(
					page.locator( '.woocommerce-review-order__meta' )
				).toContainText( `Order #${ order.id }` );

				const rows = page.locator( '.woocommerce-review-order__item' );
				await expect( rows ).toHaveCount( 2 );

				const submit = page.locator(
					'.woocommerce-review-order__submit'
				);
				await expect( submit ).toBeDisabled();

				// Rate row A with 3 stars (label index counts from 1).
				const firstRow = rows.nth( 0 );
				await rateRow( firstRow, 3 );

				// The dynamic caption reflects the chosen rating.
				await expect(
					firstRow.locator( '.woocommerce-star-rating__caption' )
				).not.toBeEmpty();
				await expect( submit ).toBeEnabled();

				await firstRow.locator( 'textarea' ).fill( 'It was fine.' );

				await submit.click();

				await expect(
					page.getByRole( 'heading', {
						name: 'Thank you for your reviews',
					} )
				).toBeVisible();
				// Meta line stays visible alongside the thank-you view.
				await expect(
					page.locator( '.woocommerce-review-order__meta' )
				).toBeVisible();

				// Verify the saved review via REST.
				const reviewsResp = await restApi.get(
					`${ WC_API_PATH }/products/reviews`,
					{ product: productIds[ 0 ], status: 'approved' }
				);
				expect(
					reviewsResp.data.find(
						( r: { review: string; rating: number } ) =>
							r.review.includes( 'It was fine' ) && r.rating === 3
					)
				).toBeTruthy();
			} finally {
				await cleanupOrder( restApi, order.id );
				await cleanupProducts( restApi, productIds );
			}
		} );

		test( 'Scenario 2 — refresh after partial submit pre-fills the submitted row', async ( {
			page,
			restApi,
		} ) => {
			const { order, productIds } = await seedCompletedOrder( restApi, [
				{ name: 'CRR Refresh A' },
				{ name: 'CRR Refresh B' },
			] );
			const url = reviewOrderUrl( order );

			try {
				await page.goto( url );

				const rows = page.locator( '.woocommerce-review-order__item' );
				const submit = page.locator(
					'.woocommerce-review-order__submit'
				);
				const rowA = rows.nth( 0 );

				await rateRow( rowA, 4 );
				await rowA
					.locator( 'textarea' )
					.fill( 'Pre-filled by Scenario 2.' );
				await submit.click();
				await expect(
					page.getByRole( 'heading', {
						name: 'Thank you for your reviews',
					} )
				).toBeVisible();

				// Refresh.
				await page.goto( url );

				const rowsAfter = page.locator(
					'.woocommerce-review-order__item'
				);
				await expect( rowsAfter ).toHaveCount( 2 );

				// Row A is pre-filled, row B is empty.
				await expect(
					rowsAfter.nth( 0 ).locator( 'textarea' )
				).toHaveValue( 'Pre-filled by Scenario 2.' );
				await expect(
					rowsAfter
						.nth( 0 )
						.locator( 'input[type="radio"][value="4"]:checked' )
				).toHaveCount( 1 );
				await expect(
					rowsAfter.nth( 1 ).locator( 'textarea' )
				).toHaveValue( '' );

				// Submit is disabled until a row diverges from its initial state.
				await expect(
					page.locator( '.woocommerce-review-order__submit' )
				).toBeDisabled();
				await rateRow( rowsAfter.nth( 1 ), 5 );
				await expect(
					page.locator( '.woocommerce-review-order__submit' )
				).toBeEnabled();
			} finally {
				await cleanupOrder( restApi, order.id );
				await cleanupProducts( restApi, productIds );
			}
		} );

		// Note: cancellation-unschedules-action coverage lives in PHPUnit
		// (SubmissionHandlerTest); the admin Scheduled Actions UI proved too
		// fragile for E2E across shards.

		test( 'Variations — two variations of one parent render two distinct rows with their attribute summaries', async ( {
			page,
			restApi,
		} ) => {
			const { order, parentId } = await seedVariationOrder( restApi, [
				'Small',
				'Medium',
			] );

			try {
				await page.goto( reviewOrderUrl( order ) );

				const rows = page.locator( '.woocommerce-review-order__item' );
				await expect( rows ).toHaveCount( 2 );

				// Both rows show the variation attribute summary inside the title.
				await expect(
					rows
						.nth( 0 )
						.locator( '.woocommerce-review-order__item-variation' )
				).toContainText( /Size:\s*Small/i );
				await expect(
					rows
						.nth( 1 )
						.locator( '.woocommerce-review-order__item-variation' )
				).toContainText( /Size:\s*Medium/i );
			} finally {
				await cleanupOrder( restApi, order.id );
				await cleanupProducts( restApi, [ parentId ] );
			}
		} );

		test( 'Variations — submitting one variation leaves the sibling row open (per-variation tracking)', async ( {
			page,
			restApi,
		} ) => {
			const { order, parentId } = await seedVariationOrder( restApi, [
				'Small',
				'Medium',
			] );

			try {
				await page.goto( reviewOrderUrl( order ) );

				const rows = page.locator( '.woocommerce-review-order__item' );

				// Submit only the Small variation.
				await rateRow( rows.nth( 0 ), 5 );
				await rows
					.nth( 0 )
					.locator( 'textarea' )
					.fill( 'Small fit great.' );

				await page
					.locator( '.woocommerce-review-order__submit' )
					.click();
				await expect(
					page.getByRole( 'heading', {
						name: 'Thank you for your reviews',
					} )
				).toBeVisible();

				// Reload — under per-variation tracking the sibling Medium row
				// is still pending, so the form stays open with both rows
				// visible. Under per-parent tracking, one review would close
				// the whole form and the thank-you state would persist.
				await page.goto( reviewOrderUrl( order ) );
				await expect(
					page.locator( '.woocommerce-review-order__item' )
				).toHaveCount( 2 );
				await expect(
					page
						.locator( '.woocommerce-review-order__item' )
						.nth( 0 )
						.locator( 'textarea' )
				).toHaveValue( 'Small fit great.' );
				await expect(
					page
						.locator( '.woocommerce-review-order__item' )
						.nth( 1 )
						.locator( 'textarea' )
				).toHaveValue( '' );
			} finally {
				await cleanupOrder( restApi, order.id );
				await cleanupProducts( restApi, [ parentId ] );
			}
		} );

		test( 'Star control — arrow keys follow the visual order and the fill tracks the selection', async ( {
			page,
			restApi,
		} ) => {
			const { order, productIds } = await seedCompletedOrder( restApi, [
				{ name: 'CRR Star Product' },
			] );

			try {
				await page.goto( reviewOrderUrl( order ) );

				// Without this the icons fade over 100ms, and an assertion
				// that a star is unfilled passes on the first sample of a
				// fill that is still fading in.
				await page.addStyleTag( {
					content:
						'.woocommerce-star-rating__icon { transition: none !important; }',
				} );

				const row = page
					.locator( '.woocommerce-review-order__item' )
					.nth( 0 );
				const starInput = ( stars: number ) =>
					row.locator(
						`.woocommerce-star-rating__input[value="${ stars }"]`
					);
				const starLabel = ( stars: number ) =>
					row.locator(
						`.woocommerce-star-rating__input[value="${ stars }"] + label`
					);
				const checkedValue = () =>
					row
						.locator( '.woocommerce-star-rating__input:checked' )
						.getAttribute( 'value' );
				const iconOpacity = ( stars: number ) =>
					starLabel( stars )
						.locator( '.woocommerce-star-rating__icon' )
						.evaluate( ( icon ) =>
							parseFloat(
								window.getComputedStyle( icon ).opacity
							)
						);
				const expectFilled = async ( stars: number ) =>
					expect( await iconOpacity( stars ) ).toBe( 1 );
				const expectUnfilled = async ( stars: number ) =>
					expect( await iconOpacity( stars ) ).toBeLessThan( 1 );

				// A floor, not an equality: below 0.55 the unfilled
				// star drops under the 3:1 of WCAG 1.4.11.
				const resting = await iconOpacity( 1 );
				expect( resting ).toBeGreaterThanOrEqual( 0.55 );
				expect( resting ).toBeLessThan( 1 );

				// Star markup is ordered 5..1, which the keys must not follow.
				await starInput( 1 ).focus();
				await page.keyboard.press( 'ArrowRight' );
				expect( await checkedValue() ).toBe( '2' );

				await page.keyboard.press( 'ArrowLeft' );
				expect( await checkedValue() ).toBe( '1' );

				await page.keyboard.press( 'End' );
				expect( await checkedValue() ).toBe( '5' );

				await page.keyboard.press( 'Home' );
				expect( await checkedValue() ).toBe( '1' );

				// A selection fills up to it and no further, and hovering a
				// higher star must add no fill the customer did not pick.
				await rateRow( row, 4 );
				for ( const stars of [ 1, 2, 3, 4 ] ) {
					await expectFilled( stars );
				}
				await expectUnfilled( 5 );

				await starLabel( 5 ).hover();
				for ( const stars of [ 1, 2, 3, 4 ] ) {
					await expectFilled( stars );
				}
				await expectUnfilled( 5 );

				// The fill still follows the next selection under that pointer.
				await page.keyboard.press( 'End' );
				expect( await checkedValue() ).toBe( '5' );
				await expectFilled( 5 );
			} finally {
				await cleanupOrder( restApi, order.id );
				await cleanupProducts( restApi, productIds );
			}
		} );

		test( 'Reflow — the page fits a 640px and a 320px viewport and stays operable', async ( {
			page,
			restApi,
		} ) => {
			// A long unbroken token is what actually stresses reflow; a short
			// name would pass on any layout.
			const { order, productIds } = await seedCompletedOrder( restApi, [
				{ name: 'CRR Reflow Unterwasserfotografieausruestungstasche' },
			] );

			try {
				const row = page
					.locator( '.woocommerce-review-order__item' )
					.nth( 0 );

				// Viewport emulation rather than real zoom: WCAG 1.4.10 states its
				// target as 320 CSS px, the width a 1280px viewport reaches at 400%
				// zoom, and 640px is the same at 200%.
				for ( const width of [ 640, 320 ] ) {
					await page.setViewportSize( { width, height: 512 } );
					await page.goto( reviewOrderUrl( order ) );

					// The success criterion itself: no horizontal page scrolling.
					const pageOverflow = await page.evaluate(
						() =>
							document.documentElement.scrollWidth -
							document.documentElement.clientWidth
					);
					expect(
						pageOverflow,
						`page scrolls horizontally at ${ width }px`
					).toBeLessThanOrEqual( 1 );

					// Then which of our own elements would cause it. Measured per
					// element because `scrollWidth` rounds to whole pixels and flakes
					// on a fractional layout, and on both edges so the mirror failure
					// in RTL is visible too.
					const overflowing = await page
						.locator( '.woocommerce-review-order' )
						.evaluate( ( root ) => {
							const bounds = root.getBoundingClientRect();
							return Array.from( root.querySelectorAll( '*' ) )
								.filter( ( el ) => {
									// A non-rendered element reports a zero-size
									// rect at the origin, which reads as overflow
									// to the left of the container.
									if ( ! el.getClientRects().length ) {
										return false;
									}
									const box = el.getBoundingClientRect();
									// 2px for sub-pixel headroom over the
									// clipped radios, which paint nothing and
									// sit exactly 1px out on `margin: -1px`.
									return (
										box.right > bounds.right + 2 ||
										box.left < bounds.left - 2
									);
								} )
								.map( ( el ) => {
									const box = el.getBoundingClientRect();
									const cls =
										el.getAttribute( 'class' ) || '';
									return `${ el.tagName }.${ cls } right${ (
										box.right - bounds.right
									).toFixed( 1 ) } left${ (
										box.left - bounds.left
									).toFixed( 1 ) }`;
								} );
						} );
					expect(
						overflowing,
						`elements past the container at ${ width }px`
					).toEqual( [] );

					await expect( row.locator( 'textarea' ) ).toBeVisible();
					await expect(
						page.locator( '.woocommerce-review-order__submit' )
					).toBeVisible();

					// Still operable at this width.
					await rateRow( row, 3 );
					await expect(
						row.locator( '.woocommerce-star-rating__input:checked' )
					).toHaveAttribute( 'value', '3' );
				}
			} finally {
				await cleanupOrder( restApi, order.id );
				await cleanupProducts( restApi, productIds );
			}
		} );

		test( 'Keyboard — submitting moves focus into the thank-you view', async ( {
			page,
			restApi,
		} ) => {
			const { order, productIds } = await seedCompletedOrder( restApi, [
				{ name: 'CRR Focus Product' },
			] );

			try {
				await page.goto( reviewOrderUrl( order ) );

				const row = page
					.locator( '.woocommerce-review-order__item' )
					.nth( 0 );
				await rateRow( row, 4 );
				await row
					.locator( 'textarea' )
					.fill( 'Submitted by keyboard.' );

				const submit = page.locator(
					'.woocommerce-review-order__submit'
				);
				await submit.focus();
				await page.keyboard.press( 'Enter' );

				await expect(
					page.getByRole( 'heading', {
						name: 'Thank you for your reviews',
					} )
				).toBeVisible();

				// The form and the focused button in it are hidden on success,
				// so focus must move to something with a name.
				await expect(
					page.locator(
						'.woocommerce-review-order__success .woocommerce-review-order__empty-title'
					)
				).toBeFocused();
			} finally {
				await cleanupOrder( restApi, order.id );
				await cleanupProducts( restApi, productIds );
			}
		} );

		test( 'Keyboard — a text-only row sends focus to its rating and links the error', async ( {
			page,
			restApi,
		} ) => {
			const { order, productIds } = await seedCompletedOrder( restApi, [
				{ name: 'CRR Validation Product' },
				{ name: 'CRR Validation Product 2' },
			] );

			try {
				await page.goto( reviewOrderUrl( order ) );

				const row = page
					.locator( '.woocommerce-review-order__item' )
					.nth( 0 );
				await row
					.locator( 'textarea' )
					.fill( 'Text without a rating.' );
				await page
					.locator( '.woocommerce-review-order__submit' )
					.click();

				// On the radio that takes focus, not on the wrapper.
				const lowest = row.locator(
					'.woocommerce-star-rating__input[value="1"]'
				);
				await expect( lowest ).toHaveAttribute(
					'aria-invalid',
					'true'
				);

				const errorId = await row
					.locator( '.woocommerce-review-order__item-rating-error' )
					.getAttribute( 'id' );
				expect(
					await lowest.getAttribute( 'aria-describedby' )
				).toContain( errorId );

				// Focus lands on the rating the customer has to fill in.
				await expect( lowest ).toBeFocused();

				// Script-moved focus after a pointer click does not match
				// `:focus-visible`; the width alone proves nothing because it
				// resolves to `medium` even with `outline-style: none`.
				const outline = await row
					.locator(
						'.woocommerce-star-rating__input[value="1"] + label'
					)
					.evaluate( ( label ) => {
						const style = window.getComputedStyle( label );
						return {
							style: style.outlineStyle,
							width: parseFloat( style.outlineWidth ),
						};
					} );
				expect( outline.style ).not.toBe( 'none' );
				expect( outline.width ).toBeGreaterThan( 0 );

				// `nearest` bottom-aligns the label, so the edge lands on the
				// boundary itself and only a whole pixel of slack is real.
				const expectInViewport = async (
					scope: Locator,
					selector: string,
					height: number,
					what: string
				) => {
					const box = ( await scope
						.locator( selector )
						.boundingBox() )!;
					expect(
						box.y,
						`${ what } above the fold at ${ height }px`
					).toBeGreaterThanOrEqual( -1 );
					expect(
						box.y + box.height,
						`${ what } below the fold at ${ height }px`
					).toBeLessThanOrEqual( height + 1 );
				};
				const submitSecondRowAt = async ( size: {
					width: number;
					height: number;
				} ) => {
					await page.setViewportSize( size );
					await page.goto( reviewOrderUrl( order ) );
					const second = page
						.locator( '.woocommerce-review-order__item' )
						.nth( 1 );
					await second
						.locator( 'textarea' )
						.fill( 'Text without a rating.' );
					await page
						.locator( '.woocommerce-review-order__submit' )
						.click();
					return second;
				};
				const errorSelector =
					'.woocommerce-review-order__item-rating-error';
				const starSelector =
					'.woocommerce-star-rating__input[value="1"] + label';

				// Stacked, the error sits above a 240px image column, which
				// puts 364px between it and the stars. Both still fit here.
				let second = await submitSecondRowAt( {
					width: 390,
					height: 664,
				} );
				await expectInViewport( second, errorSelector, 664, 'error' );
				await expectInViewport( second, starSelector, 664, 'star' );

				// 320x256 is 1280x1024 at the 400% zoom WCAG 1.4.10 names, and
				// the pair cannot both fit: the ring wins, because it is the
				// clipped radios' only indicator and `aria-describedby` still
				// carries the message.
				second = await submitSecondRowAt( { width: 320, height: 256 } );
				await expectInViewport( second, starSelector, 256, 'star' );
			} finally {
				await cleanupOrder( restApi, order.id );
				await cleanupProducts( restApi, productIds );
			}
		} );

		test( 'Keyboard — dismissing the disabled-products notice keeps focus on the page', async ( {
			page,
			restApi,
		} ) => {
			const { order, productIds } = await seedCompletedOrder( restApi, [
				{ name: 'CRR Reviewable Product' },
				{ name: 'CRR Closed Product', reviews_allowed: false },
			] );

			try {
				await page.goto( reviewOrderUrl( order ) );

				const notice = page.locator(
					'.woocommerce-review-order__notice'
				);
				await expect( notice ).toBeVisible();

				await notice
					.locator( '.woocommerce-review-order__notice-dismiss' )
					.click();

				await expect( notice ).toBeHidden();
				// Focus must not fall back to the document.
				await expect(
					page.locator( '.woocommerce-review-order__title' )
				).toBeFocused();
			} finally {
				await cleanupOrder( restApi, order.id );
				await cleanupProducts( restApi, productIds );
			}
		} );
	}
);
