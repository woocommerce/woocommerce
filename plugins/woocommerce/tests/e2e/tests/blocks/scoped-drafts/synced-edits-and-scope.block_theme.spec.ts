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

	test( 'for a variable product, quantity and attribute edits land in the shared page scope; a second page-wide surface reflects them without reverting either form; a Single Product block override resolves its own scope', async ( {
		page,
		requestUtils,
		frontendUtils,
	} ) => {
		const hoodieId = await getPostIdBySlug( 'hoodie' );

		// A second page-wide surface (no scope declaration of its own, so it
		// shares the page's scope exactly like the main form) sits alongside
		// the main form and the Single Product block override, mirroring the
		// simple-product case above. For a variable product this also
		// exercises variation resolution: the second surface's own
		// quantity- and attribute-selection watches re-run whenever the
		// shared page-wide variation resolves, so this covers that a
		// surface which never received its own edit displays the resolved
		// selection and quantity without ever writing its own stale local
		// state back over them, and that it can submit exactly what it
		// displays.
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
<!-- wp:woocommerce/single-product {"productId":${ hoodieId }} -->
<div class="wp-block-woocommerce-single-product woocommerce"><!-- wp:woocommerce/add-to-cart-with-options /--></div>
<!-- /wp:woocommerce/single-product -->
</main>
<!-- /wp:group -->
<!-- wp:template-part {"slug":"footer"} /-->`,
		} );

		await page.goto( '/product/hoodie/' );

		const forms = addToCartWithOptionsForms( page );
		await expect( forms ).toHaveCount( 3 );
		const mainForm = forms.nth( 0 );
		const secondSurface = forms.nth( 1 );
		const overriddenForm = forms.nth( 2 );

		await test.step( 'configuring the main form writes quantity and attributes into the shared page-scope draft; the second page-wide surface displays the edit and can submit it, without either form’s own values reverting; the override’s own scope stays untouched', async () => {
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

			// The second surface never received its own edit, yet its
			// quantity input and attribute chips display exactly what the
			// main form just set — both surfaces read the same shared
			// page-scope draft.
			const secondSurfaceQuantity =
				secondSurface.getByLabel( 'Product quantity' );
			await expect( secondSurfaceQuantity ).toHaveValue( '3' );
			await expect(
				secondSurface
					.getByRole( 'radiogroup', { name: 'Color' } )
					.getByRole( 'radio', { name: 'Blue', exact: true } )
			).toBeChecked();
			await expect(
				secondSurface
					.getByRole( 'radiogroup', { name: 'Logo' } )
					.getByRole( 'radio', { name: 'No', exact: true } )
			).toBeChecked();

			// Hold steady rather than merely agreeing for a moment: the
			// second surface's own quantity- and attribute-resolution
			// watches keep re-running while the shared variation stays
			// resolved, so give them a beat and confirm neither surface's
			// own values were written back to a stale local default in the
			// meantime.
			// eslint-disable-next-line playwright/no-wait-for-timeout, no-restricted-syntax
			await page.waitForTimeout( 2000 );
			await expect( mainQuantity ).toHaveValue( '3' );
			await expect( secondSurfaceQuantity ).toHaveValue( '3' );
			await expect(
				mainForm
					.getByRole( 'radiogroup', { name: 'Color' } )
					.getByRole( 'radio', { name: 'Blue', exact: true } )
			).toBeChecked();
			await expect(
				mainForm
					.getByRole( 'radiogroup', { name: 'Logo' } )
					.getByRole( 'radio', { name: 'No', exact: true } )
			).toBeChecked();
			await expect(
				secondSurface
					.getByRole( 'radiogroup', { name: 'Color' } )
					.getByRole( 'radio', { name: 'Blue', exact: true } )
			).toBeChecked();
			await expect(
				secondSurface
					.getByRole( 'radiogroup', { name: 'Logo' } )
					.getByRole( 'radio', { name: 'No', exact: true } )
			).toBeChecked();

			// The second surface displays a fully resolved, purchasable
			// configuration, so its own Add to cart must act on it.
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
