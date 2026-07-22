/**
 * External dependencies
 */
import { test as base, expect, getPostIdBySlug } from '@woocommerce/e2e-utils';

/**
 * E2E flows for the `wc-bundle-demo` fixture: a minimal bundle-style Store
 * API extension built entirely on today's extension points (`ExtendSchema`)
 * and the public `woocommerce/cart` Interactivity API store surface
 * (`upsertDraftItem`, `addItem( payload )`) — proving that a third-party
 * "bundle" of independently configurable child products can be composed and
 * added as one cart line with no core changes. See
 * `tests/e2e/test-plugins/blocks/bundle-demo.php` / `bundle-demo.js` for the
 * fixture itself.
 *
 * Each `[wc_bundle_demo]` shortcode renders two child "slots" (`slot-1`,
 * `slot-2`), each declaring its own literal, namespaced `woocommerce/cart`
 * draft key (`wc-bundle-demo/slot-1` / `wc-bundle-demo/slot-2` — the same
 * container primitive core blocks use, addressed directly from markup with
 * no registry of any kind), plus an "Add bundle to cart" button that
 * composes both slots' current drafts into one `cart/add-item` payload for
 * the bundle product, carrying a `wc-bundle-demo/children` prop. The flows
 * below configure the two slots (once with distinct child products, once
 * with the same product in both slots), add the bundle, and confirm — by
 * reading the Store API cart response directly — that the bundle lands as
 * a single line whose `extensions['wc-bundle-demo'].children` carries
 * exactly what was configured, with no collision between the slots, a
 * plain standalone form for one of the same child products elsewhere on
 * the page, or (in the same-product case) the two same-product slots
 * themselves.
 */

const BUNDLE_DEMO_PLUGIN = 'woocommerce-blocks-test-wc-bundle-demo';

type CartItem = {
	id: number;
	quantity: number;
	extensions: { 'wc-bundle-demo'?: { children: unknown[] } };
};

const test = base.extend( {} );

test.describe( 'Scoped drafts: bundle-demo extension adds as one unit', () => {
	// Activate in `beforeEach` because the helper plugin is deactivated
	// when the DB is reset — mirrors `cart-store/cart-line-identity.block_theme.spec.ts`.
	test.beforeEach( async ( { requestUtils } ) => {
		await requestUtils.activatePlugin( BUNDLE_DEMO_PLUGIN );
	} );

	test( 'distinct child products: the bundle adds as one unit carrying its own data, and a plain form for one child elsewhere stays independent', async ( {
		page,
		requestUtils,
		frontendUtils,
	} ) => {
		const beltId = await getPostIdBySlug( 'belt' );
		const beanieId = await getPostIdBySlug( 'beanie' );
		const tshirtId = await getPostIdBySlug( 't-shirt' );

		// The shortcode itself needs no block markup — `do_shortcode` runs
		// on `the_content` regardless of whether the surrounding content is
		// blockified — so it is authored as plain text alongside a Single
		// Product block wrapping a *plain* Add to Cart with Options form
		// for one of the bundle's own child products (Beanie), standing in
		// for "the same product rendered elsewhere on the page".
		const post = await requestUtils.rest( {
			method: 'POST',
			path: '/wp/v2/posts',
			data: {
				status: 'publish',
				title: 'Bundle demo: distinct children',
				content: `[wc_bundle_demo bundle="${ beltId }" child_a="${ beanieId }" child_b="${ tshirtId }"]

<!-- wp:woocommerce/single-product {"productId":${ beanieId }} -->
<div class="wp-block-woocommerce-single-product woocommerce"><!-- wp:woocommerce/add-to-cart-with-options /--></div>
<!-- /wp:woocommerce/single-product -->`,
			},
		} );

		await page.goto( `/?p=${ post.id }` );

		const slots = page.locator( '.wc-bundle-demo__slot' );
		await expect( slots ).toHaveCount( 2 );
		const slotAQuantity = slots.nth( 0 ).locator( 'input[type="number"]' );
		const slotBQuantity = slots.nth( 1 ).locator( 'input[type="number"]' );

		const plainForm = page.locator(
			'[data-block-name="woocommerce/single-product"] [data-block-name="woocommerce/add-to-cart-with-options"]'
		);
		const plainFormQuantity = plainForm.getByLabel( 'Product quantity' );

		await test.step( 'configuring each slot and the plain form leaves the others untouched', async () => {
			await plainFormQuantity.fill( '4' );
			await plainFormQuantity.blur();

			await slotAQuantity.fill( '2' );
			await slotAQuantity.dispatchEvent( 'change' );
			await slotBQuantity.fill( '3' );
			await slotBQuantity.dispatchEvent( 'change' );

			// The plain form's own draft (a Single Product block's own
			// declared draft key) is untouched by either slot's edit (each
			// slot resolves its own declared key).
			await expect( plainFormQuantity ).toHaveValue( '4' );
			// Slot A's own edit is untouched by slot B's.
			await expect( slotAQuantity ).toHaveValue( '2' );
		} );

		await test.step( 'adding the bundle posts one line carrying both slots’ drafts as wc-bundle-demo/children; the plain form adds its own independent line', async () => {
			const bundleBatchPromise = page.waitForResponse(
				'**/wc/store/v1/batch**'
			);
			await page
				.getByRole( 'button', { name: 'Add bundle to cart' } )
				.click();
			await bundleBatchPromise;

			const plainBatchPromise = page.waitForResponse(
				'**/wc/store/v1/batch**'
			);
			await plainForm
				.getByRole( 'button', { name: 'Add to cart' } )
				.click();
			await plainBatchPromise;

			await frontendUtils.goToCart();

			const cartResponse = await page.request.get(
				'/wp-json/wc/store/v1/cart'
			);
			const cart: { items: CartItem[] } = await cartResponse.json();

			const bundleLine = cart.items.find(
				( item ) => item.id === Number( beltId )
			);
			expect( bundleLine ).toMatchObject( {
				quantity: 1,
				extensions: {
					'wc-bundle-demo': {
						children: [
							{ id: Number( beanieId ), quantity: 2 },
							{ id: Number( tshirtId ), quantity: 3 },
						],
					},
				},
			} );

			// The plain form's own add is a genuinely separate cart line
			// for the same product (Beanie) the bundle's slot A also
			// drafted — its quantity (4) is neither merged into, nor
			// overwritten by, the bundle's own children data. Every cart
			// item gets the `wc-bundle-demo` extension's readback (the
			// schema extension always attaches it), but this line's own
			// `children` list is empty: it was never part of the bundle.
			const plainLine = cart.items.find(
				( item ) => item.id === Number( beanieId )
			);
			expect( plainLine ).toMatchObject( {
				quantity: 4,
				extensions: { 'wc-bundle-demo': { children: [] } },
			} );

			// Exactly two lines: the bundle unit and the plain form's own
			// line. Neither child product was ever added as its own
			// top-level line by the bundle itself.
			expect( cart.items ).toHaveLength( 2 );
		} );
	} );

	test( 'same product in both slots: each slot keeps its own draft, and the bundle composes both into one line', async ( {
		page,
		requestUtils,
		frontendUtils,
	} ) => {
		const beltId = await getPostIdBySlug( 'belt' );
		const tshirtId = await getPostIdBySlug( 't-shirt' );

		const post = await requestUtils.rest( {
			method: 'POST',
			path: '/wp/v2/posts',
			data: {
				status: 'publish',
				title: 'Bundle demo: same product in both slots',
				content: `[wc_bundle_demo bundle="${ beltId }" child_a="${ tshirtId }" child_b="${ tshirtId }"]

<!-- wp:woocommerce/single-product {"productId":${ tshirtId }} -->
<div class="wp-block-woocommerce-single-product woocommerce"><!-- wp:woocommerce/add-to-cart-with-options /--></div>
<!-- /wp:woocommerce/single-product -->`,
			},
		} );

		await page.goto( `/?p=${ post.id }` );

		const slots = page.locator( '.wc-bundle-demo__slot' );
		await expect( slots ).toHaveCount( 2 );
		const slotAQuantity = slots.nth( 0 ).locator( 'input[type="number"]' );
		const slotBQuantity = slots.nth( 1 ).locator( 'input[type="number"]' );

		const plainForm = page.locator(
			'[data-block-name="woocommerce/single-product"] [data-block-name="woocommerce/add-to-cart-with-options"]'
		);
		const plainFormQuantity = plainForm.getByLabel( 'Product quantity' );

		await test.step( 'both slots and the plain form draft the same product independently', async () => {
			await slotAQuantity.fill( '2' );
			await slotAQuantity.dispatchEvent( 'change' );
			await slotBQuantity.fill( '5' );
			await slotBQuantity.dispatchEvent( 'change' );
			await plainFormQuantity.fill( '7' );
			await plainFormQuantity.blur();

			// Each slot's own declared draft key keeps its own collection
			// — picking the same product in the other slot, or in the
			// unrelated plain form, never overwrote it.
			await expect( slotAQuantity ).toHaveValue( '2' );
			await expect( slotBQuantity ).toHaveValue( '5' );
		} );

		await test.step( 'the bundle adds one line whose children carry both slots’ own quantities; the plain form adds its own separate line', async () => {
			const bundleBatchPromise = page.waitForResponse(
				'**/wc/store/v1/batch**'
			);
			await page
				.getByRole( 'button', { name: 'Add bundle to cart' } )
				.click();
			await bundleBatchPromise;

			const plainBatchPromise = page.waitForResponse(
				'**/wc/store/v1/batch**'
			);
			await plainForm
				.getByRole( 'button', { name: 'Add to cart' } )
				.click();
			await plainBatchPromise;

			await frontendUtils.goToCart();

			const cartResponse = await page.request.get(
				'/wp-json/wc/store/v1/cart'
			);
			const cart: { items: CartItem[] } = await cartResponse.json();

			const bundleLine = cart.items.find(
				( item ) => item.id === Number( beltId )
			);
			expect( bundleLine ).toMatchObject( {
				quantity: 1,
				extensions: {
					'wc-bundle-demo': {
						// Two independent entries for the *same* product id
						// — proof the two slots' drafts were never merged
						// into one.
						children: [
							{ id: Number( tshirtId ), quantity: 2 },
							{ id: Number( tshirtId ), quantity: 5 },
						],
					},
				},
			} );

			// The plain form's own line for the same product (T-Shirt) —
			// distinct from the bundle line above, and carrying no
			// children of its own.
			const plainLine = cart.items.find(
				( item ) => item.id === Number( tshirtId )
			);
			expect( plainLine ).toMatchObject( {
				quantity: 7,
				extensions: { 'wc-bundle-demo': { children: [] } },
			} );

			expect( cart.items ).toHaveLength( 2 );
		} );
	} );

	test( 'a direct mutation of a slot’s draft repaints its own bound display with no action call, and the add-to-cart payload honors the directly-written quantity', async ( {
		page,
		requestUtils,
	} ) => {
		const beltId = await getPostIdBySlug( 'belt' );
		const beanieId = await getPostIdBySlug( 'beanie' );
		const tshirtId = await getPostIdBySlug( 't-shirt' );

		const post = await requestUtils.rest( {
			method: 'POST',
			path: '/wp/v2/posts',
			data: {
				status: 'publish',
				title: 'Bundle demo: direct mutation',
				content: `[wc_bundle_demo bundle="${ beltId }" child_a="${ beanieId }" child_b="${ tshirtId }"]`,
			},
		} );

		await page.goto( `/?p=${ post.id }` );

		const slots = page.locator( '.wc-bundle-demo__slot' );
		await expect( slots ).toHaveCount( 2 );
		const slotAQuantity = slots.nth( 0 ).locator( 'input[type="number"]' );
		const slotADisplay = slots
			.nth( 0 )
			.locator( '.wc-bundle-demo__slot-quantity' );
		const slotBDisplay = slots
			.nth( 1 )
			.locator( '.wc-bundle-demo__slot-quantity' );

		// The fixture's quantity input has no action-bound submit step of its
		// own — a slot's first edit creates its draft (`upsertDraftItem`) and
		// every edit after that is a direct mutation of the resolved draft
		// (`draft.quantity = value`); neither path is an action call.
		// Recording every `wc/store/v1/batch` request from here on lets the
		// assertions below prove that editing a slot's quantity fires none —
		// only the explicit "Add bundle to cart" click does.
		const batchRequestUrls: string[] = [];
		page.on( 'request', ( request ) => {
			if ( request.url().includes( '/wc/store/v1/batch' ) ) {
				batchRequestUrls.push( request.url() );
			}
		} );

		await test.step( 'both slots seed their bound display from the markup’s default quantity', async () => {
			await expect( slotADisplay ).toHaveText( '1' );
			await expect( slotBDisplay ).toHaveText( '1' );
		} );

		await test.step( 'a first edit creates slot A’s draft, a second edit directly mutates it and repaints only slot A’s own bound display, with no batch request fired', async () => {
			// Slot A has no draft yet — this first edit creates one via the
			// fixture's public `upsertDraftItem` creation convenience.
			await slotAQuantity.fill( '3' );
			await slotAQuantity.dispatchEvent( 'change' );

			// Slot A's draft now exists — this second edit is a direct
			// mutation of the already-resolved draft object
			// (`draft.quantity = value`), never an action call.
			await slotAQuantity.fill( '5' );
			await slotAQuantity.dispatchEvent( 'change' );

			// Re-render observed through the getter-bound `<span>` — proof
			// the writes reached the resolved draft — with no action call
			// in between: no request was posted to reach this repaint.
			await expect( slotADisplay ).toHaveText( '5' );
			expect( batchRequestUrls ).toHaveLength( 0 );

			// Neither of slot A's edits ever alters slot B's own resolved
			// collection — slot B is left untouched throughout this test,
			// so its display still reads the rendered-default fallback.
			await expect( slotBDisplay ).toHaveText( '1' );
		} );

		await test.step( 'adding the bundle posts a single request carrying only slot A’s directly-written quantity; slot B, never edited, composes nothing', async () => {
			const bundleBatchPromise = page.waitForResponse(
				'**/wc/store/v1/batch**'
			);
			await page
				.getByRole( 'button', { name: 'Add bundle to cart' } )
				.click();
			await bundleBatchPromise;

			// Exactly one batch request across the whole test: neither of
			// slot A's edits above triggered one, and this click triggered
			// exactly one — the only action call in the flow.
			expect( batchRequestUrls ).toHaveLength( 1 );

			const cartResponse = await page.request.get(
				'/wp-json/wc/store/v1/cart'
			);
			const cart: { items: CartItem[] } = await cartResponse.json();

			const bundleLine = cart.items.find(
				( item ) => item.id === Number( beltId )
			);
			// Posting reads each slot's declared collection at call time, so
			// the payload carries slot A's directly-written quantity (5),
			// not its earlier value (3) or the rendered default (1). Slot B
			// was never edited, so it has no collection at all and
			// composes nothing — an untouched slot posting nothing is the
			// safe, expected outcome, not a stale value.
			expect( bundleLine ).toMatchObject( {
				quantity: 1,
				extensions: {
					'wc-bundle-demo': {
						children: [ { id: Number( beanieId ), quantity: 5 } ],
					},
				},
			} );
		} );
	} );
} );
