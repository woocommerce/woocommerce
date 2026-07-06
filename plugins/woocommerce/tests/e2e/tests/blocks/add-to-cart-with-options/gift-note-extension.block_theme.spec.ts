/**
 * External dependencies
 */
import { test, expect, wpCLI } from '@woocommerce/e2e-utils';

/**
 * T8 — Gift Note reference extension: the acceptance test for the shared iAPI
 * stores extension contract (use cases E23 + E50).
 *
 * The extension (`woocommerce-blocks-test-plugins/wc-gift-note-demo`) contains
 * ZERO submission code and ZERO core changes. It proves, via `test.step`s:
 *
 * (a) a note typed on the product page travels to the cart line and displays;
 * (b) two different notes → two separate, separately-removable lines;
 * (c) the field round-trips the draft note the shared store owns;
 * (d) with a plain draft (no note), the two decorated lines do not pair
 *     (`itemInContext.cart` undefined / `isInCart` false — the presence
 *     heuristic), asserted on the envelope via `findItem`;
 * (e) the `cartItemFilter` demo pairs a line the generic rules would not.
 *
 * WHY ONE TEST (not five): the Add to Cart + Options block renders its iAPI form
 * (with the hooked gift-note field) only on the FIRST front-end product render
 * per PHP worker; on subsequent renders in the same worker it flips to its
 * LEGACY add-to-cart markup (no iAPI form, no field). This is an ATCWO /
 * test-environment statefulness issue, NOT an extension-contract problem — the
 * field and all three server hooks work correctly on every clean render (see the
 * T8 report for the full root-cause). Multiple product-page visits WITHIN one
 * test keep the iAPI form; crossing a test boundary flips it. So the whole
 * contract is exercised inside a single test's first-render window.
 */

const PRODUCT = 'Beanie';
const PRODUCT_SLUG = 'beanie';

const CART_LOCK =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

// A single-product template whose add-to-cart region is the iAPI Add to Cart +
// Options block (the form the gift-note field is injected into), plus the
// cartItemFilter demo badge. Set via the REST API so it is guest-visible and
// does not depend on the site editor.
const SINGLE_PRODUCT_TEMPLATE_CONTENT = `
<!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group">
<!-- wp:post-title {"level":1} /-->
<!-- wp:woocommerce/product-price /-->
<!-- wp:woocommerce/add-to-cart-with-options /-->
<!-- wp:wc-gift-note-demo/badge {"noteMarker":"VIP"} /-->
</div>
<!-- /wp:group -->
`.trim();

/**
 * Add the product from its page with an optional gift note, and wait for the
 * add-item batch to settle.
 */
async function addWithNote(
	page: import( '@playwright/test' ).Page,
	note?: string
) {
	await page.goto( `/product/${ PRODUCT_SLUG }/` );
	const noteInput = page.getByLabel( 'Gift note' );
	await expect( noteInput ).toBeVisible();
	if ( note !== undefined ) {
		await noteInput.fill( note );
	}
	const batch = page.waitForResponse( '**/wc/store/v1/batch**' );
	await page
		.getByRole( 'button', { name: /add to cart/i } )
		.first()
		.click();
	await batch;
}

test.describe( 'Add to Cart + Options — Gift Note extension (T8)', () => {
	test.beforeAll( async ( { requestUtils } ) => {
		await wpCLI(
			'plugin activate woocommerce-blocks-test-plugins/wc-gift-note-demo'
		);
		await requestUtils.createTemplate( 'wp_template', {
			slug: 'single-product',
			title: 'Gift Note Single Product',
			content: SINGLE_PRODUCT_TEMPLATE_CONTENT,
		} );
	} );

	test.afterAll( async ( { requestUtils } ) => {
		await requestUtils.deleteAllTemplates( 'wp_template' );
		await wpCLI(
			'plugin deactivate woocommerce-blocks-test-plugins/wc-gift-note-demo'
		);
	} );

	test( 'proves the extension contract end to end', async ( {
		page,
		frontendUtils,
	} ) => {
		await test.step( '(a) note travels to the cart line and displays', async () => {
			await addWithNote( page, 'Happy Birthday!' );

			await frontendUtils.goToCart();

			await expect(
				page.getByText( 'Gift note', { exact: false } ).first()
			).toBeVisible();
			await expect(
				page.getByText( 'Happy Birthday!' )
			).toBeVisible();
		} );

		await test.step( '(b) two different notes → two separate, removable lines', async () => {
			await addWithNote( page, 'Note B' );

			await frontendUtils.goToCart();

			// Two note-split lines: the first note ("Happy Birthday!") and the
			// new one both present; the product title appears twice.
			await expect( page.getByText( 'Happy Birthday!' ) ).toBeVisible();
			await expect( page.getByText( 'Note B' ) ).toBeVisible();
			await expect(
				page.getByRole( 'link', { name: PRODUCT, exact: true } )
			).toHaveCount( 2 );

			// Independently removable: removing one leaves the other.
			const removeButtons = page.getByLabel(
				/Remove Beanie from cart/
			);
			await expect( removeButtons ).toHaveCount( 2 );
			const batch = page.waitForResponse( '**/wc/store/v1/batch**' );
			await removeButtons.first().click();
			await batch;
			await expect(
				page.getByRole( 'link', { name: PRODUCT, exact: true } )
			).toHaveCount( 1 );
		} );

		await test.step( '(c) the field round-trips the draft note the store owns', async () => {
			await page.goto( `/product/${ PRODUCT_SLUG }/` );
			const noteInput = page.getByLabel( 'Gift note' );
			await expect( noteInput ).toBeVisible();

			// Typing writes the draft; the bound value reads it back — the note
			// lives in the shared `woocommerce/cart` draft (upsertDraftItem), not
			// in local component state.
			await noteInput.fill( 'Round trip' );
			await expect( noteInput ).toHaveValue( 'Round trip' );

			const draftNote = await page.evaluate( async ( lock ) => {
				const { store } = await import( '@wordpress/interactivity' );
				await import( '@woocommerce/stores/woocommerce/cart' );
				const { state } = store(
					'woocommerce/cart',
					{},
					{ lock }
				);
				const drafts = state.draftItems || [];
				const withNote = drafts.find(
					( d: Record< string, unknown > ) =>
						d[ 'wc-gift-note-demo/gift-note' ] === 'Round trip'
				);
				return withNote ? 'found' : 'missing';
			}, CART_LOCK );
			expect( draftNote ).toBe( 'found' );
		} );

		await test.step( '(d) a plain draft (no note) does not pair the decorated lines', async () => {
			// The cart currently holds one note-carrying line (from step b) plus
			// the "Round trip" draft has no matching line. Read the envelope for a
			// note-less draft: the presence heuristic must exclude the decorated
			// line → cart undefined, isInCart false.
			const env = await page.evaluate( async ( lock ) => {
				const { store } = await import( '@wordpress/interactivity' );
				await import( '@woocommerce/stores/woocommerce/cart' );
				const { state } = store(
					'woocommerce/cart',
					{},
					{ lock }
				);
				const productId = ( state.draftItems || [] )[ 0 ]?.id as
					| number
					| undefined;
				if ( ! productId ) {
					return { error: 'no-draft' };
				}
				// findItem with an id but no note props builds a bare draft and
				// runs the generic ladder (filter: false forces generic rules).
				const envelope = state.findItem( {
					id: productId,
					filter: false,
				} );
				return {
					hasCart: Boolean( envelope?.cart ),
					isInCart: envelope?.isInCart ?? null,
				};
			}, CART_LOCK );

			expect( env ).not.toHaveProperty( 'error' );
			expect( ( env as { hasCart: boolean } ).hasCart ).toBe( false );
			expect( ( env as { isInCart: boolean } ).isInCart ).toBe( false );
		} );

		await test.step( '(e) the cartItemFilter demo pairs a line the generic rules would not', async () => {
			// Add a line whose note starts with the badge's marker.
			await addWithNote( page, 'VIP - handle with care' );

			// Back on the product page the seeded draft has no note (generic rules
			// would exclude the decorated line via the presence heuristic), but the
			// badge's `cartItemFilter` predicate matches by marker prefix and pairs
			// exactly that line → the badge (bound to `itemInContext.cart`) shows.
			await page.goto( `/product/${ PRODUCT_SLUG }/` );
			await page.waitForResponse( '**/wc/store/v1/cart**' );

			await expect( page.getByTestId( 'gift-note-badge' ) ).toBeVisible();
		} );
	} );
} );
