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
 * rendered as a sibling in the template content — it declares no draft key
 * of its own, so it resolves the same page-wide (global-key) draft
 * collection as the main form), and a Single Product block wrapping a
 * further rendering of the same product (a container that declares its own
 * minted `single-product/<productId>/<n>` key, isolating its own draft
 * collection).
 */
const addToCartWithOptionsForms = ( page: import('@playwright/test').Page ) =>
	page.locator( '[data-block-name="woocommerce/add-to-cart-with-options"]' );

test.describe( 'Scoped drafts: synced page-wide surfaces; Single Product block stays isolated', () => {
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
		// template alongside an isolating Single Product block on one page:
		// the template's ambient product context (established by WordPress
		// for any singular product view, independent of which blocks are
		// present) is what makes the first two renderings "page-wide" in
		// the first place.
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

			// The second surface resolves the same page-wide draft
			// collection the main form just wrote to, so its own quantity
			// input repaints to match — a shopper looking at the second
			// surface sees the same value the main form was just set to,
			// not a stale one.
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

		await test.step( 'the isolated Single Product block was never affected by the page-wide edit, and its own edit adds independently', async () => {
			await page.goto( '/product/beanie/' );

			// Still its own untouched default: the page-wide edit above
			// never reached this container's own collection.
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
			// page-wide collection's draft.
			await expect(
				readCartLineQuantities( page, 'Beanie' )
			).resolves.toEqual( [ 8 ] );
		} );
	} );

	test( 'for a variable product, quantity and attribute edits land in the shared page-wide collection; a second page-wide surface reflects them without reverting either form; a Single Product block override resolves its own collection', async ( {
		page,
		requestUtils,
		frontendUtils,
	} ) => {
		const hoodieId = await getPostIdBySlug( 'hoodie' );

		// A second page-wide surface (no draft key of its own, so it
		// resolves the same global-key collection exactly like the main form)
		// sits alongside the main form and the Single Product block
		// override, mirroring the simple-product case above. For a variable
		// product this also exercises variation resolution: the second
		// surface's own quantity- and attribute-selection watches re-run
		// whenever the shared page-wide variation resolves, so this covers
		// that a surface which never received its own edit displays the
		// resolved selection and quantity without ever writing its own
		// stale local state back over them, and that it can submit exactly
		// what it displays.
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

		await test.step( 'configuring the main form writes quantity and attributes into the shared page-wide draft; the second page-wide surface displays the edit and can submit it, without either form’s own values reverting; the override’s own collection stays untouched', async () => {
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
			// asynchronously (it watches the in-context product data), and
			// gates the main form's own Add to cart on that same resolved
			// selection — so waiting for it to clear its disabled state is
			// the shopper-visible sign that the shared page-wide draft now
			// carries the fully resolved variation and quantity.
			const mainAddToCartButton = mainForm.getByRole( 'button', {
				name: 'Add to cart',
			} );
			await expect( mainAddToCartButton ).not.toHaveClass(
				/\bdisabled\b/
			);

			// The container's own draft collection — addressed by the
			// minted `single-product/<productId>/<n>` key the Single
			// Product block declares, as emitted by `SingleProduct.php` —
			// never received the main form's edit. Untouched, it holds no
			// draft at all, so the override's own inputs still display
			// their server-rendered defaults, exactly as they started.
			await expect(
				overriddenForm.getByLabel( 'Product quantity' )
			).toHaveValue( '1' );
			await expect(
				overriddenForm
					.getByRole( 'radiogroup', { name: 'Color' } )
					.getByRole( 'radio', { name: 'Blue', exact: true } )
			).not.toBeChecked();
			await expect(
				overriddenForm
					.getByRole( 'radiogroup', { name: 'Logo' } )
					.getByRole( 'radio', { name: 'No', exact: true } )
			).not.toBeChecked();

			// The second surface never received its own edit, yet its
			// quantity input and attribute chips display exactly what the
			// main form just set — both surfaces resolve the same shared
			// page-wide draft collection.
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

		await test.step( 'the override resolves its own collection: configuring a different variation there adds an independent cart line', async () => {
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
