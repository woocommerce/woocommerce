/**
 * E2E coverage for the Customer Review Request → Review Order page.
 * Products/orders seeded via REST for speed; everything else via UI.
 * Tracked as WOOPLUG-6601 (Linear).
 */

/**
 * External dependencies
 */
import { request, type Locator } from '@playwright/test';
import { ApiClient, WC_API_PATH } from '@woocommerce/e2e-utils-playwright';

/**
 * Internal dependencies
 */
import { ADMIN_STATE_PATH } from '../../playwright.config';
import { tags, expect, test } from '../../fixtures/fixtures';
import { setOption, deleteOption } from '../../utils/options';
import { random } from '../../utils/helpers';

type SeededOrder = {
	id: number;
	key: string;
};

// `page` is logged-out (Review Order is guest-accessible via order key);
// `restApi` is admin basic-auth.

const FEATURE_FLAG_OPTION =
	'woocommerce_feature_customer_review_request_enabled';
const SITE_REVIEWS_OPTION = 'woocommerce_enable_reviews';

test.describe(
	'Customer Review Request — Review Order page',
	{ tag: [ tags.SERVICES, tags.HPOS ] },
	() => {
		test.beforeAll( async ( { baseURL } ) => {
			// Email settings option (array) is left absent so defaults apply;
			// api_update_option only handles strings anyway.
			await setOption(
				request,
				baseURL || '',
				FEATURE_FLAG_OPTION,
				'yes'
			);
		} );

		test.afterAll( async ( { baseURL } ) => {
			await deleteOption( request, baseURL || '', FEATURE_FLAG_OPTION );
		} );

		// `wc_get_review_order_url()` shape; built in JS since the email's
		// 1-day minimum delay rules out a real "click the link" flow in CI.
		const reviewOrderUrl = ( order: SeededOrder ): string =>
			`?wc-review-order=${ order.id }&key=${ order.key }`;

		// Star inputs are CSS-hidden behind their <label>s for a11y; clicking
		// the label triggers the radio without needing { force: true }.
		const rateRow = ( row: Locator, stars: number ) =>
			row
				.locator( 'label.woocommerce-star-rating__star' )
				.filter( {
					hasText: new RegExp( `${ stars } out of 5 stars` ),
				} )
				.click();

		const cleanupProducts = async ( restApi: ApiClient, ids: number[] ) => {
			for ( const id of ids ) {
				if ( id <= 0 ) {
					continue;
				}
				await restApi
					.delete( `${ WC_API_PATH }/products/${ id }`, {
						force: true,
					} )
					.catch( () => undefined );
			}
		};

		const cleanupOrder = async ( restApi: ApiClient, id: number ) => {
			if ( id <= 0 ) {
				return;
			}
			await restApi
				.delete( `${ WC_API_PATH }/orders/${ id }`, { force: true } )
				.catch( () => undefined );
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

		test( 'Scenario 3 — per-product reviews disabled hides the row and shows the dismissible notice', async ( {
			page,
			restApi,
		} ) => {
			const { order, productIds } = await seedCompletedOrder( restApi, [
				{ name: 'CRR Reviewable' },
				{
					name: 'CRR Reviews Off',
					reviews_allowed: false,
				},
			] );

			try {
				await page.goto( reviewOrderUrl( order ) );

				const rows = page.locator( '.woocommerce-review-order__item' );
				await expect( rows ).toHaveCount( 1 );
				await expect( rows.nth( 0 ) ).toContainText( 'CRR Reviewable' );

				const notice = page.locator(
					'.woocommerce-review-order__notice'
				);
				await expect( notice ).toBeVisible();
				await expect( notice ).toContainText(
					"Don't see all your products?"
				);

				await page
					.locator( '.woocommerce-review-order__notice-dismiss' )
					.click();
				await expect( notice ).toBeHidden();
			} finally {
				await cleanupOrder( restApi, order.id );
				await cleanupProducts( restApi, productIds );
			}
		} );

		test( 'Scenario 4 — site-wide reviews disabled renders the empty-state thank-you', async ( {
			page,
			baseURL,
			restApi,
		} ) => {
			const { order, productIds } = await seedCompletedOrder( restApi, [
				{ name: 'CRR Site-wide Off' },
			] );

			try {
				await setOption(
					request,
					baseURL || '',
					SITE_REVIEWS_OPTION,
					'no'
				);

				await page.goto( reviewOrderUrl( order ) );

				await expect(
					page.getByRole( 'heading', {
						name: 'Nothing to review here',
					} )
				).toBeVisible();
				await expect(
					page.locator( '.woocommerce-review-order__form' )
				).toHaveCount( 0 );
				await expect(
					page.locator( '.woocommerce-review-order__submit' )
				).toHaveCount( 0 );
			} finally {
				// Fresh-install default is 'yes'.
				await setOption(
					request,
					baseURL || '',
					SITE_REVIEWS_OPTION,
					'yes'
				);
				await cleanupOrder( restApi, order.id );
				await cleanupProducts( restApi, productIds );
			}
		} );

		test( 'Scenario 5 — cancelling the order unschedules the pending review-request action', async ( {
			browser,
			restApi,
		} ) => {
			const { order, productIds } = await seedCompletedOrder( restApi, [
				{ name: 'CRR Cancel Flow' },
			] );

			// Admin context: the Scheduled Actions list lives in wp-admin.
			const adminContext = await browser.newContext( {
				storageState: ADMIN_STATE_PATH,
			} );
			const adminPage = await adminContext.newPage();
			const scheduledActionsUrl = `wp-admin/admin.php?page=wc-status&tab=action-scheduler&s=woocommerce_send_review_request&search-args=${ order.id }&orderby=schedule&order=desc`;

			try {
				await adminPage.goto( scheduledActionsUrl );
				await expect(
					adminPage.locator(
						`tbody tr td.column-args:has-text("${ order.id }")`
					)
				).toHaveCount( 1 );

				await restApi.put( `${ WC_API_PATH }/orders/${ order.id }`, {
					status: 'cancelled',
				} );

				await adminPage.goto( scheduledActionsUrl );
				await expect(
					adminPage.locator(
						`tbody tr td.column-args:has-text("${ order.id }")`
					)
				).toHaveCount( 0 );
			} finally {
				await adminContext.close();
				await cleanupOrder( restApi, order.id );
				await cleanupProducts( restApi, productIds );
			}
		} );

		test( 'Scenario 6 — typing review text without a rating surfaces the inline error', async ( {
			page,
			restApi,
		} ) => {
			const { order, productIds } = await seedCompletedOrder( restApi, [
				{ name: 'CRR Rating Required' },
			] );

			try {
				await page.goto( reviewOrderUrl( order ) );

				const row = page
					.locator( '.woocommerce-review-order__item' )
					.first();
				await row.locator( 'textarea' ).fill( 'Loved it.' );
				await page
					.locator( '.woocommerce-review-order__submit' )
					.click();

				const error = row.locator(
					'.woocommerce-review-order__item-rating-error'
				);
				await expect( error ).toBeVisible();
				await expect( error ).toContainText(
					'Please rate this product before submitting your review.'
				);
				// Form did not submit.
				await expect(
					page.getByRole( 'heading', {
						name: 'Thank you for your reviews',
					} )
				).toHaveCount( 0 );

				// Selecting a rating clears the error.
				await rateRow( row, 5 );
				await expect( error ).toBeHidden();

				// Submitting now succeeds.
				await page
					.locator( '.woocommerce-review-order__submit' )
					.click();
				await expect(
					page.getByRole( 'heading', {
						name: 'Thank you for your reviews',
					} )
				).toBeVisible();
			} finally {
				await cleanupOrder( restApi, order.id );
				await cleanupProducts( restApi, productIds );
			}
		} );

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

		test( 'Variations — submitting both variation rows stores per-variation reviews and the parent Reviews tab surfaces the variation summary', async ( {
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

				await rateRow( rows.nth( 0 ), 5 );
				await rows
					.nth( 0 )
					.locator( 'textarea' )
					.fill( 'Small fit great.' );

				await rateRow( rows.nth( 1 ), 3 );
				await rows
					.nth( 1 )
					.locator( 'textarea' )
					.fill( 'Medium ran short.' );

				await page
					.locator( '.woocommerce-review-order__submit' )
					.click();
				await expect(
					page.getByRole( 'heading', {
						name: 'Thank you for your reviews',
					} )
				).toBeVisible();

				// Parent product page: each review surfaces its own variation
				// summary. If linkage were wrong, the wrong summary would sit
				// next to each review and the per-text assertions would fail.
				const { data: parentProduct } = await restApi.get(
					`${ WC_API_PATH }/products/${ parentId }`
				);
				await page.goto( parentProduct.permalink );
				// eslint-disable-next-line playwright/no-conditional-in-test -- classic themes hide reviews behind a tab; block themes don't.
				const reviewsTab = page.locator(
					'#tab-title-reviews a, a[href="#tab-reviews"], a[href="#reviews"]'
				);
				// eslint-disable-next-line playwright/no-conditional-in-test -- see above.
				if ( ( await reviewsTab.count() ) > 0 ) {
					await reviewsTab.first().click();
				}

				const smallComment = page
					.locator( 'li.comment, li[class*="comment"]' )
					.filter( { hasText: 'Small fit great' } )
					.first();
				await expect(
					smallComment.locator(
						'.woocommerce-review__variation-summary'
					)
				).toContainText( /Size:\s*Small/i );

				const mediumComment = page
					.locator( 'li.comment, li[class*="comment"]' )
					.filter( { hasText: 'Medium ran short' } )
					.first();
				await expect(
					mediumComment.locator(
						'.woocommerce-review__variation-summary'
					)
				).toContainText( /Size:\s*Medium/i );
			} finally {
				await cleanupOrder( restApi, order.id );
				await cleanupProducts( restApi, [ parentId ] );
			}
		} );
	}
);
