/**
 * E2E coverage for the Customer Review Request → Review Order page flow.
 *
 * Each test seeds its own products / order via the WC REST API (which uses
 * its own basic-auth client, independent of the browser session), then opens
 * the page logged-out, mirroring the real customer who reaches the URL via
 * the tokenized email link. The page URL itself is read back from
 * `wc_get_review_order_url()` over wp-cli so the test can't drift from the
 * helper it's exercising.
 *
 * Tracked as WOOPLUG-6601 (Linear).
 */

/**
 * External dependencies
 */
import { ApiClient, WC_API_PATH } from '@woocommerce/e2e-utils-playwright';

/**
 * Internal dependencies
 */
import { tags, expect, test } from '../../fixtures/fixtures';
import { wpCLI } from '../../utils/cli';
import { random } from '../../utils/helpers';

type SeededOrder = {
	id: number;
	key: string;
};

// Browser context is logged-out by default — Review Order is a guest-friendly
// surface and the order key is the auth. The `restApi` fixture is admin
// (basic-auth) regardless.

const FEATURE_FLAG_OPTION =
	'woocommerce_feature_customer_review_request_enabled';
const REQUEST_SETTINGS_OPTION = 'woocommerce_customer_review_request_settings';
const SITE_REVIEWS_OPTION = 'woocommerce_enable_reviews';

test.describe(
	'Customer Review Request — Review Order page',
	{ tag: [ tags.SERVICES, tags.HPOS ] },
	() => {
		let originalFeatureFlag = '';
		let originalRequestSettings = '';
		let originalSiteReviews = '';

		const readOption = async ( name: string ): Promise< string > => {
			const { stdout } = await wpCLI(
				`wp option get ${ name } --format=json`
			);
			return stdout.trim();
		};

		const writeOption = async (
			name: string,
			value: string
		): Promise< void > => {
			if ( value === '' ) {
				await wpCLI( `wp option delete ${ name }` );
				return;
			}
			// `wp option set --format=json` accepts JSON whether the option is a
			// scalar string or an array — works for both `enabled` (string) and
			// the request settings (array).
			await wpCLI(
				`wp option set ${ name } ${ JSON.stringify(
					value
				) } --format=json`
			);
		};

		test.beforeAll( async () => {
			// Capture original option values so afterAll can restore them; the
			// test site shouldn't be left in a feature-enabled state.
			originalFeatureFlag = await readOption( FEATURE_FLAG_OPTION );
			originalRequestSettings = await readOption(
				REQUEST_SETTINGS_OPTION
			);
			originalSiteReviews = await readOption( SITE_REVIEWS_OPTION );

			// Enable the feature flag.
			await wpCLI( `wp option set ${ FEATURE_FLAG_OPTION } yes` );

			// Force the request settings option to a known shape with the
			// transactional email enabled. Using `wp option set --format=json`
			// (vs. `wp option patch update`) so it works on a fresh site where
			// the option doesn't exist yet.
			await wpCLI(
				`wp option set ${ REQUEST_SETTINGS_OPTION } ` +
					"'" +
					JSON.stringify( { enabled: 'yes' } ) +
					"' --format=json"
			);

			await wpCLI( 'wp rewrite flush' );
		} );

		test.afterAll( async () => {
			// Restore each option to the exact state we found it in.
			if ( originalFeatureFlag === '' ) {
				await wpCLI( `wp option delete ${ FEATURE_FLAG_OPTION }` );
			} else {
				await wpCLI(
					`wp option set ${ FEATURE_FLAG_OPTION } ` +
						JSON.stringify( originalFeatureFlag ) +
						' --format=json'
				);
			}

			if ( originalRequestSettings === '' ) {
				await wpCLI( `wp option delete ${ REQUEST_SETTINGS_OPTION }` );
			} else {
				await wpCLI(
					`wp option set ${ REQUEST_SETTINGS_OPTION } ` +
						"'" +
						originalRequestSettings +
						"' --format=json"
				);
			}

			if ( originalSiteReviews === '' ) {
				await wpCLI( `wp option delete ${ SITE_REVIEWS_OPTION }` );
			} else {
				await wpCLI(
					`wp option set ${ SITE_REVIEWS_OPTION } ` +
						JSON.stringify( originalSiteReviews ) +
						' --format=json'
				);
			}
		} );

		/**
		 * Resolve the Review Order URL through the WC helper rather than
		 * building it by hand, so the test exercises the same path the email
		 * templates use.
		 */
		const reviewOrderUrl = async (
			order: SeededOrder
		): Promise< string > => {
			const { stdout } = await wpCLI(
				`wp eval "echo wc_get_review_order_url( wc_get_order( ${ order.id } ) );"`
			);
			return stdout.trim();
		};

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
				await page.goto( await reviewOrderUrl( order ) );

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
				await firstRow
					.getByRole( 'radio', { name: /3 out of 5 stars/ } )
					.check();

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
			const url = await reviewOrderUrl( order );

			try {
				await page.goto( url );

				const rows = page.locator( '.woocommerce-review-order__item' );
				const submit = page.locator(
					'.woocommerce-review-order__submit'
				);
				const rowA = rows.nth( 0 );

				await rowA
					.getByRole( 'radio', { name: /4 out of 5 stars/ } )
					.check();
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
				await rowsAfter
					.nth( 1 )
					.getByRole( 'radio', { name: /5 out of 5 stars/ } )
					.check();
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
				await page.goto( await reviewOrderUrl( order ) );

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
			restApi,
		} ) => {
			const { order, productIds } = await seedCompletedOrder( restApi, [
				{ name: 'CRR Site-wide Off' },
			] );
			const originalSiteReviewsForThisTest = await readOption(
				SITE_REVIEWS_OPTION
			);

			try {
				await wpCLI( `wp option set ${ SITE_REVIEWS_OPTION } no` );

				await page.goto( await reviewOrderUrl( order ) );

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
				await writeOption(
					SITE_REVIEWS_OPTION,
					originalSiteReviewsForThisTest
				);
				await cleanupOrder( restApi, order.id );
				await cleanupProducts( restApi, productIds );
			}
		} );

		test( 'Scenario 5 — cancelling the order unschedules the pending review-request action', async ( {
			restApi,
		} ) => {
			const { order, productIds } = await seedCompletedOrder( restApi, [
				{ name: 'CRR Cancel Flow' },
			] );

			const hasScheduledAction = async () => {
				const { stdout } = await wpCLI(
					`wp eval "echo as_next_scheduled_action( 'woocommerce_send_review_request', array( ${ order.id } ) ) ? '1' : '0';"`
				);
				return stdout.trim().endsWith( '1' );
			};

			try {
				expect( await hasScheduledAction() ).toBe( true );

				await restApi.put( `${ WC_API_PATH }/orders/${ order.id }`, {
					status: 'cancelled',
				} );

				expect( await hasScheduledAction() ).toBe( false );
			} finally {
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
				await page.goto( await reviewOrderUrl( order ) );

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
				await row
					.getByRole( 'radio', { name: /5 out of 5 stars/ } )
					.check();
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
				await page.goto( await reviewOrderUrl( order ) );

				const rows = page.locator( '.woocommerce-review-order__item' );
				await expect( rows ).toHaveCount( 2 );

				// Both rows show the variation attribute summary inside the title.
				await expect(
					rows
						.nth( 0 )
						.locator( '.woocommerce-review-order__item-variation' )
				).toContainText( /Size:\s*Small/ );
				await expect(
					rows
						.nth( 1 )
						.locator( '.woocommerce-review-order__item-variation' )
				).toContainText( /Size:\s*Medium/ );
			} finally {
				await cleanupOrder( restApi, order.id );
				await cleanupProducts( restApi, [ parentId ] );
			}
		} );

		test( 'Variations — submitting both variation rows stores per-variation reviews and the parent Reviews tab surfaces the variation summary', async ( {
			page,
			restApi,
		} ) => {
			const { order, parentId, variationIds } = await seedVariationOrder(
				restApi,
				[ 'Small', 'Medium' ]
			);
			const [ smallVariationId, mediumVariationId ] = variationIds;

			try {
				await page.goto( await reviewOrderUrl( order ) );

				const rows = page.locator( '.woocommerce-review-order__item' );

				await rows
					.nth( 0 )
					.getByRole( 'radio', { name: /5 out of 5 stars/ } )
					.check();
				await rows
					.nth( 0 )
					.locator( 'textarea' )
					.fill( 'Small fit great.' );

				await rows
					.nth( 1 )
					.getByRole( 'radio', { name: /3 out of 5 stars/ } )
					.check();
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

				// Pull the parent's reviews; assert each comment's variation
				// meta points at the matching variation, not just that two
				// reviews exist with the expected text.
				const reviewsResp = await restApi.get(
					`${ WC_API_PATH }/products/reviews`,
					{ product: parentId, status: 'approved' }
				);
				const reviews = (
					reviewsResp.data as Array< {
						id: number;
						review: string;
						rating: number;
					} >
				 ).filter( ( r ) =>
					/Small fit great|Medium ran short/.test( r.review )
				);
				expect( reviews ).toHaveLength( 2 );

				const variationIdForReview = async (
					commentId: number
				): Promise< number > => {
					const { stdout } = await wpCLI(
						`wp eval "echo (int) get_comment_meta( ${ commentId }, '_review_variation_id', true );"`
					);
					return Number( stdout.trim() );
				};

				const requireReview = (
					predicate: ( r: ( typeof reviews )[ number ] ) => boolean,
					label: string
				) => {
					const found = reviews.find( predicate );
					// eslint-disable-next-line playwright/no-conditional-in-test -- narrowing for downstream property access.
					if ( ! found ) {
						throw new Error(
							`Expected to find a review matching ${ label }, none returned by the REST API.`
						);
					}
					return found;
				};

				const smallReview = requireReview(
					( r ) => r.review.includes( 'Small fit great' ),
					'"Small fit great"'
				);
				const mediumReview = requireReview(
					( r ) => r.review.includes( 'Medium ran short' ),
					'"Medium ran short"'
				);
				expect( await variationIdForReview( smallReview.id ) ).toBe(
					smallVariationId
				);
				expect( await variationIdForReview( mediumReview.id ) ).toBe(
					mediumVariationId
				);
				expect( smallReview.rating ).toBe( 5 );
				expect( mediumReview.rating ).toBe( 3 );

				// Parent product page surfaces each review with its own
				// variation summary. Open the Reviews tab when the active
				// theme uses the classic tabbed layout (Storefront, etc.);
				// block themes render the list inline, in which case the
				// click target won't exist.
				const { data: parentProduct } = await restApi.get(
					`${ WC_API_PATH }/products/${ parentId }`
				);
				await page.goto( parentProduct.permalink );
				// eslint-disable-next-line playwright/no-conditional-in-test -- block themes render the reviews inline, classic themes hide them behind a tab; both must work.
				const reviewsTab = page.locator(
					'#tab-title-reviews a, a[href="#tab-reviews"], a[href="#reviews"]'
				);
				// eslint-disable-next-line playwright/no-conditional-in-test -- see above.
				if ( ( await reviewsTab.count() ) > 0 ) {
					await reviewsTab.first().click();
				}

				// Assert each review's variation summary sits next to its body.
				const smallComment = page
					.locator( 'li.comment, li[class*="comment"]' )
					.filter( { hasText: 'Small fit great' } )
					.first();
				await expect(
					smallComment.locator(
						'.woocommerce-review__variation-summary'
					)
				).toContainText( /Size:\s*Small/ );

				const mediumComment = page
					.locator( 'li.comment, li[class*="comment"]' )
					.filter( { hasText: 'Medium ran short' } )
					.first();
				await expect(
					mediumComment.locator(
						'.woocommerce-review__variation-summary'
					)
				).toContainText( /Size:\s*Medium/ );
			} finally {
				await cleanupOrder( restApi, order.id );
				await cleanupProducts( restApi, [ parentId ] );
			}
		} );
	}
);
