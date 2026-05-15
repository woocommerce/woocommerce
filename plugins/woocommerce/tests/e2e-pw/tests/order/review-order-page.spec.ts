/**
 * E2E coverage for the Customer Review Request → Review Order page flow.
 *
 * Each test seeds its own products / order via REST, then opens the Review
 * Order URL (`/review-order/{id}/?key={order_key}`) directly — no need to
 * trigger the email pipeline since the page is the actual surface under
 * test and the URL is deterministic.
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
import { ADMIN_STATE_PATH } from '../../playwright.config';
import { wpCLI } from '../../utils/cli';
import { random } from '../../utils/helpers';

type ReviewOrderRow = {
	id: number;
	key: string;
};

test.use( { storageState: ADMIN_STATE_PATH } );

const ALL_PRODUCT_REVIEWS_ENABLED = 'yes';

test.describe(
	'Customer Review Request — Review Order page',
	{ tag: [ tags.SERVICES, tags.HPOS ] },
	() => {
		test.beforeAll( async () => {
			// Enable the feature flag and the transactional email so the
			// `/review-order/{id}` route is registered and submissions write
			// per-order review meta. Action Scheduler scheduling is gated on
			// these too, but the tests bypass that path by hitting the URL
			// directly.
			await wpCLI(
				'wp option set woocommerce_feature_customer_review_request_enabled yes'
			);
			await wpCLI(
				`wp option patch update woocommerce_customer_review_request_settings enabled yes`
			);
			await wpCLI( 'wp rewrite flush' );
		} );

		test.afterAll( async () => {
			await wpCLI(
				`wp option update woocommerce_enable_reviews ${ ALL_PRODUCT_REVIEWS_ENABLED }`
			);
			await wpCLI(
				`wp option patch update woocommerce_customer_review_request_settings enabled no`
			);
			await wpCLI(
				'wp option update woocommerce_feature_customer_review_request_enabled no'
			);
		} );

		const reviewOrderUrl = ( { id, key }: ReviewOrderRow ) =>
			`/review-order/${ id }/?key=${ key }`;

		/**
		 * Create N simple products plus an open order containing them, all in
		 * `completed` status so the Review Order page renders.
		 *
		 * @param restApi        Playwright fixture rest client.
		 * @param productConfigs One config per product. Each can override `reviews_allowed`.
		 * @return                    Created order id + key + the product ids in input order.
		 */
		const seedCompletedOrder = async (
			restApi: ApiClient,
			productConfigs: Array< {
				name?: string;
				reviews_allowed?: boolean;
			} >
		): Promise< { order: ReviewOrderRow; productIds: number[] } > => {
			const productIds: number[] = [];
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
					line_items: productIds.map( ( id ) => ( {
						product_id: id,
						quantity: 1,
					} ) ),
				}
			);

			return {
				order: { id: order.id, key: order.order_key },
				productIds,
			};
		};

		/**
		 * Create a variable product with `variationOptions.length` variations
		 * of a single `Size` attribute, all reviewable, plus an order
		 * containing each variation.
		 */
		const seedVariationOrder = async (
			restApi: ApiClient,
			variationOptions: string[]
		): Promise< {
			order: ReviewOrderRow;
			parentId: number;
			variationIds: number[];
		} > => {
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

			const variationIds: number[] = [];
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
					line_items: variationIds.map( ( vid ) => ( {
						product_id: parent.id,
						variation_id: vid,
						quantity: 1,
					} ) ),
				}
			);

			return {
				order: { id: order.id, key: order.order_key },
				parentId: parent.id,
				variationIds,
			};
		};

		const cleanupProducts = async ( restApi: ApiClient, ids: number[] ) => {
			for ( const id of ids ) {
				await restApi.delete( `${ WC_API_PATH }/products/${ id }`, {
					force: true,
				} );
			}
		};

		const cleanupOrder = async ( restApi: ApiClient, id: number ) => {
			await restApi.delete( `${ WC_API_PATH }/orders/${ id }`, {
				force: true,
			} );
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
					{ product: productIds[ 0 ] }
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

			try {
				await page.goto( reviewOrderUrl( order ) );

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
				await page.goto( reviewOrderUrl( order ) );

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
			restApi,
		} ) => {
			const { order, productIds } = await seedCompletedOrder( restApi, [
				{ name: 'CRR Site-wide Off' },
			] );

			try {
				await wpCLI( 'wp option update woocommerce_enable_reviews no' );

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
				await wpCLI(
					`wp option update woocommerce_enable_reviews ${ ALL_PRODUCT_REVIEWS_ENABLED }`
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
				await page.goto( reviewOrderUrl( order ) );

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
			const { order, parentId } = await seedVariationOrder( restApi, [
				'Small',
				'Medium',
			] );

			try {
				await page.goto( reviewOrderUrl( order ) );

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

				// Two distinct reviews recorded for the parent product.
				const reviewsResp = await restApi.get(
					`${ WC_API_PATH }/products/reviews`,
					{ product: parentId }
				);
				const reviewsForParent = (
					reviewsResp.data as Array< {
						review: string;
						rating: number;
					} >
				 ).filter( ( r ) =>
					/Small fit great|Medium ran short/.test( r.review )
				);
				expect( reviewsForParent.length ).toBe( 2 );

				// Parent product page surfaces the variation summary above each comment.
				const { data: parentProduct } = await restApi.get(
					`${ WC_API_PATH }/products/${ parentId }`
				);
				await page.goto( parentProduct.permalink );
				const summaries = page.locator(
					'.woocommerce-review__variation-summary'
				);
				await expect( summaries ).toHaveCount( 2 );
				await expect(
					summaries.filter( { hasText: /Size:\s*Small/ } )
				).toHaveCount( 1 );
				await expect(
					summaries.filter( { hasText: /Size:\s*Medium/ } )
				).toHaveCount( 1 );
			} finally {
				await cleanupOrder( restApi, order.id );
				await cleanupProducts( restApi, [ parentId ] );
			}
		} );
	}
);
