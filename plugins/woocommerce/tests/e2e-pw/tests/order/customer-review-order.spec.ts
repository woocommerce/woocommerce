/**
 * External dependencies
 */
import { WC_API_PATH } from '@woocommerce/e2e-utils-playwright';

/**
 * Internal dependencies
 */
import { tags, expect, test } from '../../fixtures/fixtures';

type OrderProduct = {
	id: number;
};

type Order = {
	id: number;
	order_key: string;
};

const productPrice = '15.99';
const successStatusSelector =
	'.woocommerce-review-order__item-status--ok, .woocommerce-review-order__item-status--pending_moderation';

test.describe.serial(
	'Customer Review Order page: rate, submit, prefill, empty-state',
	{ tag: [ tags.HPOS ] },
	() => {
		const products: OrderProduct[] = [];
		let order: Order;
		const customerEmail = `review-${ Date.now() }@example.test`;

		test.beforeAll( async ( { restApi } ) => {
			for ( let i = 0; i < 3; i++ ) {
				const response = await restApi.post(
					`${ WC_API_PATH }/products`,
					{
						name: `Reviewable Product ${ i + 1 }`,
						type: 'simple',
						regular_price: productPrice,
						reviews_allowed: true,
					}
				);
				products.push( {
					id: response.data.id,
				} );
			}

			const orderResponse = await restApi.post(
				`${ WC_API_PATH }/orders`,
				{
					status: 'completed',
					billing: {
						first_name: 'Review',
						last_name: 'Customer',
						email: customerEmail,
					},
					line_items: products.map( ( p ) => ( {
						product_id: p.id,
						quantity: 1,
					} ) ),
				}
			);
			order = orderResponse.data;
		} );

		test.afterAll( async ( { restApi } ) => {
			// Best-effort cleanup: a beforeAll() failure may have left
			// $order or $products partially populated. Swallow per-resource
			// errors so the original test failure stays visible.
			for ( const product of products ) {
				try {
					await restApi.delete(
						`${ WC_API_PATH }/products/${ product.id }`,
						{ force: true }
					);
				} catch ( _err ) {}
			}
			if ( order && order.id ) {
				try {
					await restApi.delete(
						`${ WC_API_PATH }/orders/${ order.id }`,
						{ force: true }
					);
				} catch ( _err ) {}
			}
		} );

		test( 'submit button is disabled until a row is dirty', async ( {
			page,
		} ) => {
			await page.goto(
				`/review-order/${ order.id }/?key=${ order.order_key }`
			);

			const submit = page.locator( '.woocommerce-review-order__submit' );
			await expect( submit ).toBeDisabled();

			// Setting a rating on any row should flip the gate open.
			await page
				.locator( '.woocommerce-review-order__item' )
				.first()
				.locator( 'label[for$="-3"]' )
				.click();
			await expect( submit ).toBeEnabled();
		} );

		test( 'typing review text without a rating blocks submission with an inline error', async ( {
			page,
		} ) => {
			await page.goto(
				`/review-order/${ order.id }/?key=${ order.order_key }`
			);

			const rows = page.locator( '.woocommerce-review-order__item' );
			const secondRow = rows.nth( 1 );

			// Type review text on row 2 WITHOUT setting a rating; the dirty-state
			// gate enables submission so the validator can fire.
			await secondRow
				.locator(
					'textarea.woocommerce-review-order__item-review-textarea'
				)
				.fill( 'I would like to leave a review.' );

			const submit = page.locator( '.woocommerce-review-order__submit' );
			await expect( submit ).toBeEnabled();
			await submit.click();

			// Inline error shows under row 2 and the wrapper does NOT enter the
			// success state, so the form chrome stays on screen.
			await expect(
				secondRow.locator(
					'.woocommerce-review-order__item-rating-error'
				)
			).toBeVisible();
			await expect(
				page.locator( '.woocommerce-review-order' )
			).not.toHaveClass( /is-success/ );

			// Setting a rating clears the error and the form is submittable.
			await secondRow.locator( 'label[for$="-4"]' ).click();
			await expect(
				secondRow.locator(
					'.woocommerce-review-order__item-rating-error'
				)
			).toHaveCount( 0 );
		} );

		test( 'guest customer rates two of three products, submits, and lands on the in-place thank-you view', async ( {
			page,
		} ) => {
			await page.goto(
				`/review-order/${ order.id }/?key=${ order.order_key }`
			);

			// Block themes render the page title in `wp-block-post-title` with
			// the same text, so scope to the shortcode-rendered heading.
			await expect(
				page.locator( '.woocommerce-review-order__title' )
			).toBeVisible();

			const rows = page.locator( '.woocommerce-review-order__item' );
			await expect( rows ).toHaveCount( products.length );

			// Rate the first product 5 stars by clicking the wrapping label.
			await rows.nth( 0 ).locator( 'label[for$="-5"]' ).click();

			// Rate the second product 4 stars and add review text.
			await rows.nth( 1 ).locator( 'label[for$="-4"]' ).click();
			await rows
				.nth( 1 )
				.locator(
					'textarea.woocommerce-review-order__item-review-textarea'
				)
				.fill( 'Solid build. Recommended.' );

			// Leave the third product untouched.

			// Submit gate should be enabled now.
			const submit = page.locator( '.woocommerce-review-order__submit' );
			await expect( submit ).toBeEnabled();
			await Promise.all( [
				// The form posts as FormData (multipart), so request.postData()
				// is unreliable for action matching. Use the URL + the JSON
				// payload shape (woocommerce_submit_order_reviews always returns
				// { success, data: { results } }) to identify the right response.
				page.waitForResponse( async ( response ) => {
					if (
						! response.url().includes( 'admin-ajax.php' ) ||
						response.request().method() !== 'POST' ||
						! response.ok()
					) {
						return false;
					}
					try {
						const body = await response.json();
						return (
							body &&
							typeof body === 'object' &&
							body.data &&
							Object.prototype.hasOwnProperty.call(
								body.data,
								'results'
							)
						);
					} catch ( _err ) {
						return false;
					}
				} ),
				submit.click(),
			] );

			// First two rows should report a successful submission. Either
			// '--ok' (auto-approved) or '--pending_moderation' (when the
			// comment_moderation option is on) counts as success; '--error'
			// would indicate a real failure. Test environment-agnostic so
			// it doesn't depend on global comment_moderation state.
			await expect(
				rows.nth( 0 ).locator( successStatusSelector )
			).toBeVisible();
			await expect(
				rows.nth( 1 ).locator( successStatusSelector )
			).toBeVisible();
			// Third row was never rated and should have no status note at all.
			await expect(
				rows
					.nth( 2 )
					.locator( '.woocommerce-review-order__item-status' )
			).toHaveCount( 0 );

			// Wrapper enters the success state: JS adds `.is-success`, the form
			// chrome hides, and the hidden thank-you block becomes visible.
			await expect(
				page.locator( '.woocommerce-review-order' )
			).toHaveClass( /is-success/ );
			await expect(
				page.locator( '.woocommerce-review-order__form' )
			).toBeHidden();
			await expect(
				page.locator(
					'.woocommerce-review-order__success .woocommerce-review-order__empty-title'
				)
			).toContainText( /thank you/i );
		} );

		test( 'reloading the page pre-fills previously submitted reviews', async ( {
			page,
		} ) => {
			await page.goto(
				`/review-order/${ order.id }/?key=${ order.order_key }`
			);

			// The third product is still unreviewed, so the form (not the
			// empty-state thank-you) is what renders. All three rows show.
			const rows = page.locator( '.woocommerce-review-order__item' );
			await expect( rows ).toHaveCount( products.length );

			// First row was rated 5 stars on the previous submission — the
			// matching radio should be pre-checked on reload.
			await expect(
				rows.nth( 0 ).locator( 'input[id$="-5"]' )
			).toBeChecked();

			// Second row: 4 stars + text are both pre-filled.
			await expect(
				rows.nth( 1 ).locator( 'input[id$="-4"]' )
			).toBeChecked();
			await expect(
				rows
					.nth( 1 )
					.locator(
						'textarea.woocommerce-review-order__item-review-textarea'
					)
			).toHaveValue( 'Solid build. Recommended.' );

			// Third row was never rated; no radio is checked and the textarea
			// is empty.
			await expect(
				rows.nth( 2 ).locator( 'input[type="radio"]:checked' )
			).toHaveCount( 0 );
			await expect(
				rows
					.nth( 2 )
					.locator(
						'textarea.woocommerce-review-order__item-review-textarea'
					)
			).toHaveValue( '' );

			// Prefilled rows mean nothing diverges from the initial state, so
			// the submit gate starts disabled again on this fresh load.
			await expect(
				page.locator( '.woocommerce-review-order__submit' )
			).toBeDisabled();
		} );

		test( 'reviewing the last remaining product lands the empty-state thank-you on reload', async ( {
			page,
		} ) => {
			await page.goto(
				`/review-order/${ order.id }/?key=${ order.order_key }`
			);

			const rows = page.locator( '.woocommerce-review-order__item' );
			const lastRow = rows.nth( 2 );

			await lastRow.locator( 'label[for$="-3"]' ).click();
			await Promise.all( [
				// Same predicate as the happy-path test: scope to the submit
				// AJAX response by the JSON shape the handler returns
				// (`{ data: { results } }`) so other admin-ajax requests
				// firing in parallel don't resolve this wait.
				page.waitForResponse( async ( response ) => {
					if (
						! response.url().includes( 'admin-ajax.php' ) ||
						response.request().method() !== 'POST' ||
						! response.ok()
					) {
						return false;
					}
					try {
						const body = await response.json();
						return (
							body &&
							typeof body === 'object' &&
							body.data &&
							Object.prototype.hasOwnProperty.call(
								body.data,
								'results'
							)
						);
					} catch ( _err ) {
						return false;
					}
				} ),
				page.locator( '.woocommerce-review-order__submit' ).click(),
			] );

			// Reload: every actionable item is now reviewed, so the dispatcher
			// routes to the empty-state thank-you template.
			await page.goto(
				`/review-order/${ order.id }/?key=${ order.order_key }`
			);
			await expect(
				page.locator( '.woocommerce-review-order--empty' )
			).toBeVisible();
			await expect(
				page.locator( '.woocommerce-review-order__empty-title' )
			).toContainText( /thank you/i );
			await expect(
				page.locator( '.woocommerce-review-order__form' )
			).toHaveCount( 0 );
		} );
	}
);

test.describe(
	'Customer Review Order page: disabled-products notice',
	{ tag: [ tags.HPOS ] },
	() => {
		const products: OrderProduct[] = [];
		let order: Order;
		const customerEmail = `review-disabled-${ Date.now() }@example.test`;

		test.beforeAll( async ( { restApi } ) => {
			// Two reviewable products, one with reviews disabled at the product
			// level — the latter goes through `ItemEligibility::STATUS_SKIP`
			// and triggers the info notice above the form.
			for ( let i = 0; i < 2; i++ ) {
				const response = await restApi.post(
					`${ WC_API_PATH }/products`,
					{
						name: `Reviewable Notice Product ${ i + 1 }`,
						type: 'simple',
						regular_price: productPrice,
						reviews_allowed: true,
					}
				);
				products.push( { id: response.data.id } );
			}
			const disabledResponse = await restApi.post(
				`${ WC_API_PATH }/products`,
				{
					name: 'Disabled Reviews Product',
					type: 'simple',
					regular_price: productPrice,
					reviews_allowed: false,
				}
			);
			products.push( { id: disabledResponse.data.id } );

			const orderResponse = await restApi.post(
				`${ WC_API_PATH }/orders`,
				{
					status: 'completed',
					billing: {
						first_name: 'Notice',
						last_name: 'Customer',
						email: customerEmail,
					},
					line_items: products.map( ( p ) => ( {
						product_id: p.id,
						quantity: 1,
					} ) ),
				}
			);
			order = orderResponse.data;
		} );

		test.afterAll( async ( { restApi } ) => {
			for ( const product of products ) {
				try {
					await restApi.delete(
						`${ WC_API_PATH }/products/${ product.id }`,
						{ force: true }
					);
				} catch ( _err ) {}
			}
			if ( order && order.id ) {
				try {
					await restApi.delete(
						`${ WC_API_PATH }/orders/${ order.id }`,
						{ force: true }
					);
				} catch ( _err ) {}
			}
		} );

		test( 'shows the info notice above the form when some items are STATUS_SKIP', async ( {
			page,
		} ) => {
			await page.goto(
				`/review-order/${ order.id }/?key=${ order.order_key }`
			);

			// Only the two reviewable products render rows; the disabled one
			// is filtered out.
			await expect(
				page.locator( '.woocommerce-review-order__item' )
			).toHaveCount( 2 );

			const notice = page.locator( '.woocommerce-review-order__notice' );
			await expect( notice ).toBeVisible();
			await expect( notice ).toContainText(
				/Don.?t see all your products/i
			);

			// The notice is layered on `.woocommerce-info` so themes that
			// restyle classic WC notices restyle this one too.
			await expect( notice ).toHaveClass( /woocommerce-info/ );

			// Dismiss button hides the notice in-page without navigating.
			await notice
				.locator( '.woocommerce-review-order__notice-dismiss' )
				.click();
			await expect( notice ).toBeHidden();
		} );
	}
);

test.describe(
	'Customer Review Order page: 404 paths',
	{ tag: [ tags.HPOS ] },
	() => {
		test( 'mismatched key renders a 404 page', async ( {
			page,
			restApi,
		} ) => {
			const productResponse = await restApi.post(
				`${ WC_API_PATH }/products`,
				{
					name: `Reviewable 404 Product`,
					type: 'simple',
					regular_price: productPrice,
					reviews_allowed: true,
				}
			);
			const productId = productResponse.data.id;
			const orderResponse = await restApi.post(
				`${ WC_API_PATH }/orders`,
				{
					status: 'completed',
					line_items: [ { product_id: productId, quantity: 1 } ],
				}
			);
			const orderId = orderResponse.data.id;

			try {
				const response = await page.goto(
					`/review-order/${ orderId }/?key=wc_order_definitelywrong`
				);
				expect( response?.status() ).toBe( 404 );
			} finally {
				try {
					await restApi.delete(
						`${ WC_API_PATH }/orders/${ orderId }`,
						{ force: true }
					);
					await restApi.delete(
						`${ WC_API_PATH }/products/${ productId }`,
						{ force: true }
					);
				} catch ( _err ) {}
			}
		} );
	}
);
