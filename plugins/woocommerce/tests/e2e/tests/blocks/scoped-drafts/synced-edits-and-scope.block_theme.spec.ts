/**
 * External dependencies
 */
import { test as base, expect, getPostIdBySlug } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { cartLineRows, readCartLineQuantities } from '../cart-store/utils';

const test = base.extend( {} );

/**
 * Locates the "Add to Cart with Options" renderings a test in this file
 * puts on the Single Product Template, in document order: the template's
 * own main form, optionally a second page-wide surface (e.g. a sticky bar,
 * rendered as a sibling in the template content — no scope declaration of
 * its own, so it shares the page's scope exactly like the main form), and a
 * Single Product block wrapping a further rendering of the same product (a
 * scope-overriding container).
 */
const addToCartWithOptionsForms = ( page: import('@playwright/test').Page ) =>
	page.locator( '[data-block-name="woocommerce/add-to-cart-with-options"]' );

/**
 * Reads the `woocommerce/cart` store's scope-keyed draft ledger directly —
 * the same technique `cart-store/mutation-batcher.block_theme.spec.ts` uses
 * to assert the store's internals. Used below to confirm that quantity
 * *and* attribute edits land together in the shared `draftItems[pageScope]`
 * entry, while a Single Product block override keeps its own separate
 * scope bucket untouched.
 */
const readCartScopeState = ( page: import('@playwright/test').Page ) =>
	page.evaluate( async () => {
		const { store } = await import( '@wordpress/interactivity' );
		const unlockKey =
			'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';
		await import( '@woocommerce/stores/woocommerce/cart' );
		const { state } = store( 'woocommerce/cart', {}, { lock: unlockKey } );
		return {
			pageScope: state.pageScope as string,
			draftItems: JSON.parse(
				JSON.stringify( state.draftItems )
			) as Record<
				string,
				{ id: number; quantity: number; variation?: unknown[] }[]
			>,
		};
	} );

test.describe( 'Scoped drafts: synced page-wide surfaces; scope override isolates', () => {
	test( 'a second page-wide surface picks up a quantity edit made on the main form; a Single Product block override never sees it', async ( {
		page,
		requestUtils,
		frontendUtils,
	} ) => {
		const beanieId = await getPostIdBySlug( 'beanie' );

		// Authors the Single Product Template directly via the REST API
		// (the same "single-product" slug the site editor customizes),
		// exactly as several pre-existing suites already do (e.g.
		// `add-to-cart-form.block_theme.spec.ts`). This is the only way to
		// place a *third*, container-free rendering of the product's own
		// template alongside a scope-overriding Single Product block on
		// one page: the template's ambient product context (established by
		// WordPress for any singular product view, independent of which
		// blocks are present) is what makes the first two renderings
		// "page-wide" in the first place.
		await requestUtils.createTemplate( 'wp_template', {
			slug: 'single-product',
			title: 'Custom Single Product',
			content: `<!-- wp:template-part {"slug":"header"} /-->
<!-- wp:group {"tagName":"main","layout":{"inherit":true,"type":"constrained"}} -->
<main class="wp-block-group">
<!-- wp:woocommerce/add-to-cart-with-options /-->
<!-- wp:group {"className":"page-wide-secondary-surface"} -->
<div class="wp-block-group page-wide-secondary-surface"><!-- wp:woocommerce/add-to-cart-with-options /--></div>
<!-- /wp:group -->
<!-- wp:woocommerce/single-product {"productId":${ beanieId }} -->
<div class="wp-block-woocommerce-single-product woocommerce"><!-- wp:woocommerce/add-to-cart-with-options /--></div>
<!-- /wp:woocommerce/single-product -->
</main>
<!-- /wp:group -->
<!-- wp:template-part {"slug":"footer"} /-->`,
		} );

		await page.goto( '/product/beanie/' );

		const forms = addToCartWithOptionsForms( page );
		await expect( forms ).toHaveCount( 3 );
		const mainForm = forms.nth( 0 );
		const secondSurface = forms.nth( 1 );
		const overriddenForm = forms.nth( 2 );

		await test.step( 'the override starts at its own untouched default', async () => {
			await expect(
				overriddenForm.getByLabel( 'Product quantity' )
			).toHaveValue( '1' );
		} );

		await test.step( 'editing the main form updates the second page-wide surface’s own display immediately; submitting from the second surface posts what it now shows', async () => {
			const mainQuantity = mainForm.getByLabel( 'Product quantity' );
			await mainQuantity.fill( '3' );
			await mainQuantity.blur();

			// The second surface shares the page scope the main form just
			// wrote to, so its own quantity input repaints to match — a
			// shopper looking at the second surface sees the same value the
			// main form was just set to, not a stale one.
			const secondSurfaceQuantity =
				secondSurface.getByLabel( 'Product quantity' );
			await expect( secondSurfaceQuantity ).toHaveValue( '3' );

			const secondSurfaceAddToCartButton = secondSurface.getByRole(
				'button',
				{ name: 'Add to cart' }
			);
			await expect( secondSurfaceAddToCartButton ).not.toHaveClass(
				/\bdisabled\b/
			);

			const batchPromise = page.waitForResponse(
				'**/wc/store/v1/batch**'
			);
			await secondSurfaceAddToCartButton.click();
			await batchPromise;

			await frontendUtils.goToCart();
			await expect(
				readCartLineQuantities( page, 'Beanie' )
			).resolves.toEqual( [ 3 ] );
		} );

		await test.step( 'the scope-overriding Single Product block was never affected by the page-wide edit, and its own edit adds independently', async () => {
			await page.goto( '/product/beanie/' );

			// Still its own untouched default: the page-wide edit above
			// never reached this container's own scope.
			await expect(
				overriddenForm.getByLabel( 'Product quantity' )
			).toHaveValue( '1' );

			const overriddenQuantity =
				overriddenForm.getByLabel( 'Product quantity' );
			await overriddenQuantity.fill( '5' );
			await overriddenQuantity.blur();

			const overriddenAddToCartButton = overriddenForm.getByRole(
				'button',
				{ name: 'Add to cart' }
			);
			const batchPromise = page.waitForResponse(
				'**/wc/store/v1/batch**'
			);
			await overriddenAddToCartButton.click();
			await batchPromise;

			await frontendUtils.goToCart();
			// Beanie has no distinguishing cart-item data either way, so
			// WooCommerce merges both adds into one line: 3 (page-wide) + 5
			// (override) = 8. The override's contribution landing
			// correctly, on top of the earlier add, is exactly what proves
			// its edit was never swallowed by (or overwritten by) the
			// page-wide scope's draft.
			await expect(
				readCartLineQuantities( page, 'Beanie' )
			).resolves.toEqual( [ 8 ] );
		} );
	} );

	test( 'for a variable product, quantity and attribute edits land in the shared page scope; a Single Product block override resolves its own scope', async ( {
		page,
		requestUtils,
		frontendUtils,
	} ) => {
		const hoodieId = await getPostIdBySlug( 'hoodie' );

		// Only two renderings here (the template's own main form, and a
		// Single Product block override) — deliberately no additional,
		// never-configured page-wide surface for this variable product.
		// Reading the store directly confirms why: a page-wide "Add to Cart
		// with Options" instance with no attribute selection of its own
		// resolves against the same page-wide product/variation state the
		// main form writes to, and its own idle re-resolution — "no
		// attributes selected" — eventually overwrites the main form's
		// selection back to that unconfigured default. Synced quantity
		// across multiple page-wide surfaces, which does not depend on any
		// variation resolution, is proven independently on a simple product
		// in the test above.
		await requestUtils.createTemplate( 'wp_template', {
			slug: 'single-product',
			title: 'Custom Single Product',
			content: `<!-- wp:template-part {"slug":"header"} /-->
<!-- wp:group {"tagName":"main","layout":{"inherit":true,"type":"constrained"}} -->
<main class="wp-block-group">
<!-- wp:woocommerce/add-to-cart-with-options /-->
<!-- wp:woocommerce/single-product {"productId":${ hoodieId }} -->
<div class="wp-block-woocommerce-single-product woocommerce"><!-- wp:woocommerce/add-to-cart-with-options /--></div>
<!-- /wp:woocommerce/single-product -->
</main>
<!-- /wp:group -->
<!-- wp:template-part {"slug":"footer"} /-->`,
		} );

		await page.goto( '/product/hoodie/' );

		const forms = addToCartWithOptionsForms( page );
		await expect( forms ).toHaveCount( 2 );
		const mainForm = forms.nth( 0 );
		const overriddenForm = forms.nth( 1 );

		await test.step( 'configuring the main form writes quantity and attributes into the shared page-scope draft, leaving the override’s own scope untouched', async () => {
			const mainQuantity = mainForm.getByLabel( 'Product quantity' );
			await mainQuantity.fill( '3' );
			await mainQuantity.blur();

			await mainForm
				.getByRole( 'radiogroup', { name: 'Color' } )
				.getByRole( 'radio', { name: 'Blue', exact: true } )
				.click();
			await mainForm
				.getByRole( 'radiogroup', { name: 'Logo' } )
				.getByRole( 'radio', { name: 'No', exact: true } )
				.click();

			// The variation selector resolves the matching variation
			// asynchronously (it watches the in-context product data), so
			// poll until the shared page-scope draft reflects the fully
			// resolved variation + quantity.
			await expect
				.poll( async () => {
					const { pageScope, draftItems } = await readCartScopeState(
						page
					);
					const pageWideDraft = draftItems[ pageScope ]?.find(
						( draft ) =>
							Array.isArray( draft.variation ) &&
							draft.variation.length > 0
					);
					return pageWideDraft?.quantity;
				} )
				.toBe( 3 );

			const { pageScope, draftItems } = await readCartScopeState( page );

			// The container's own scope — `single-product/<id>/<n>`, as
			// minted by `SingleProduct.php` — still only has its untouched
			// server-seeded default: the main form's edit never reached it.
			const overriddenScope = Object.keys( draftItems ).find(
				( scope ) => scope !== pageScope
			);
			expect( overriddenScope ).toBeDefined();
			expect( draftItems[ overriddenScope as string ] ).toEqual( [
				expect.objectContaining( {
					id: Number( hoodieId ),
					quantity: 1,
					variation: [],
				} ),
			] );

			// Submit the main form's resolved draft now, before the next
			// step configures the override — the override carries its own
			// local product context (established by `SingleProduct.php`),
			// so configuring it further below never touches the main
			// form's own selection; this ordering just keeps the two
			// submissions, and their resulting cart lines, easy to tell
			// apart.
			const mainBatchPromise = page.waitForResponse(
				'**/wc/store/v1/batch**'
			);
			await mainForm
				.getByRole( 'button', { name: 'Add to cart' } )
				.click();
			await mainBatchPromise;

			await frontendUtils.goToCart();
			await expect(
				readCartLineQuantities( page, 'Hoodie' )
			).resolves.toEqual( [ 3 ] );
			const blueRow = cartLineRows( page, 'Hoodie' ).filter( {
				hasText: 'Blue',
			} );
			await expect( blueRow ).toHaveCount( 1 );
		} );

		await test.step( 'the override resolves its own scope: configuring a different variation there adds an independent cart line', async () => {
			await page.goto( '/product/hoodie/' );

			await overriddenForm
				.getByRole( 'radiogroup', { name: 'Color' } )
				.getByRole( 'radio', { name: 'Green', exact: true } )
				.click();
			await overriddenForm
				.getByRole( 'radiogroup', { name: 'Logo' } )
				.getByRole( 'radio', { name: 'No', exact: true } )
				.click();
			const overriddenQuantity =
				overriddenForm.getByLabel( 'Product quantity' );
			await overriddenQuantity.fill( '2' );
			await overriddenQuantity.blur();

			const overriddenBatchPromise = page.waitForResponse(
				'**/wc/store/v1/batch**'
			);
			await overriddenForm
				.getByRole( 'button', { name: 'Add to cart' } )
				.click();
			await overriddenBatchPromise;

			await frontendUtils.goToCart();
			// Two independent lines for the same parent product: the
			// page-wide Blue/No line added in the previous step, untouched,
			// plus the override's own independent Green/No line.
			await expect(
				readCartLineQuantities( page, 'Hoodie' )
			).resolves.toEqual( [ 2, 3 ] );

			const blueRow = cartLineRows( page, 'Hoodie' ).filter( {
				hasText: 'Blue',
			} );
			const greenRow = cartLineRows( page, 'Hoodie' ).filter( {
				hasText: 'Green',
			} );
			await expect( blueRow ).toHaveCount( 1 );
			await expect( greenRow ).toHaveCount( 1 );
			await expect(
				blueRow.getByLabel( 'Quantity of Hoodie in your cart.' )
			).toHaveValue( '3' );
			await expect(
				greenRow.getByLabel( 'Quantity of Hoodie in your cart.' )
			).toHaveValue( '2' );
		} );
	} );
} );
