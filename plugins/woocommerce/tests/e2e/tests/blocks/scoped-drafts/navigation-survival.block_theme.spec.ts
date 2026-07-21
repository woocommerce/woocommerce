/**
 * External dependencies
 */
import { test as base, expect, getPostIdBySlug } from '@woocommerce/e2e-utils';

/**
 * `wc-navigation-survival`: proves that a draft held in the public
 * `woocommerce/cart` store's keyed global state survives a genuine
 * client-side navigation between two ordinary pages, on the stock,
 * supported region-based Interactivity API router — no experimental
 * full-page navigation mode, no runtime patch. See
 * `tests/e2e/test-plugins/blocks/navigation-survival.php` /
 * `navigation-survival.js` for the fixture itself.
 *
 * Both pages share one top-level `data-wp-router-region` id, so the
 * router's region-matching swap keeps the JS runtime (and the cart
 * store's global draft state) alive across the navigation instead of
 * reloading the document. Page A carries two purchase surfaces for the
 * same product — one declaring no `woocommerce/cart` context of its own
 * ("unwrapped", resolving the store's usual fallback collection, exactly
 * like a plain, container-free Add to Cart with Options form) and one
 * wrapped in the fixture's own declared draft key ("keyed", isolated by
 * that key) — while page B carries only the unwrapped surface for the same
 * product. The flow below edits both of page A's surfaces, navigates
 * client-side to page B and back, and confirms: the unwrapped edit is
 * visible on both pages' unwrapped surfaces (they resolve the identical
 * fallback collection), the keyed edit survives independently and never
 * leaks into page B's unwrapped surface, and a hard reload resets both of
 * page A's surfaces to their server-seeded defaults.
 */

const NAVIGATION_SURVIVAL_PLUGIN =
	'woocommerce-blocks-test-navigation-survival';

const test = base.extend( {} );

test.describe( 'Scoped drafts: cross-page client-side navigation survival', () => {
	// Activate in `beforeEach` because the helper plugin is deactivated
	// when the DB is reset — mirrors `bundle-demo.block_theme.spec.ts`.
	test.beforeEach( async ( { requestUtils } ) => {
		await requestUtils.activatePlugin( NAVIGATION_SURVIVAL_PLUGIN );
	} );

	test( "an unwrapped edit survives a genuine client-side round trip and is shared across pages; a keyed surface's edit survives isolated from the other page's unwrapped surface; a hard reload resets both", async ( {
		page,
		requestUtils,
	} ) => {
		const beanieId = await getPostIdBySlug( 'beanie' );

		// Page A is created first, empty, so its id is known when page B's
		// content is authored linking back to it; page A's own content
		// (linking forward to B) is filled in afterwards, once B's id is
		// known too — the two pages' links are mutually referential, so
		// one of them has to be back-filled.
		const pageA = await requestUtils.rest( {
			method: 'POST',
			path: '/wp/v2/posts',
			data: {
				status: 'publish',
				title: 'Navigation survival: page A',
				content: '',
			},
		} );

		const pageB = await requestUtils.rest( {
			method: 'POST',
			path: '/wp/v2/posts',
			data: {
				status: 'publish',
				title: 'Navigation survival: page B',
				content: `[wc_navigation_survival product="${ beanieId }" page="b" target="/?p=${ pageA.id }" link_text="Go to page A"]`,
			},
		} );

		await requestUtils.rest( {
			method: 'PUT',
			path: `/wp/v2/posts/${ pageA.id }`,
			data: {
				content: `[wc_navigation_survival product="${ beanieId }" page="a" target="/?p=${ pageB.id }" link_text="Go to page B"]`,
			},
		} );

		const pageAUrl = `/?p=${ pageA.id }`;
		const pageBUrl = `/?p=${ pageB.id }`;

		const unwrappedQuantity = () =>
			page
				.locator( '.wc-navigation-survival__surface--unwrapped' )
				.locator( 'input[type="number"]' );
		const keyedQuantity = () =>
			page
				.locator( '.wc-navigation-survival__surface--keyed' )
				.locator( 'input[type="number"]' );

		await test.step( 'both pages start at their own server-seeded defaults; page B carries no keyed surface at all', async () => {
			await page.goto( pageBUrl );
			await expect( unwrappedQuantity() ).toHaveValue( '1' );
			await expect( keyedQuantity() ).toHaveCount( 0 );

			await page.goto( pageAUrl );
			await expect( unwrappedQuantity() ).toHaveValue( '1' );
			await expect( keyedQuantity() ).toHaveValue( '1' );
		} );

		await test.step( "editing page A's unwrapped and keyed surfaces updates their own displays", async () => {
			await unwrappedQuantity().fill( '4' );
			await unwrappedQuantity().dispatchEvent( 'change' );
			await keyedQuantity().fill( '7' );
			await keyedQuantity().dispatchEvent( 'change' );

			await expect( unwrappedQuantity() ).toHaveValue( '4' );
			await expect( keyedQuantity() ).toHaveValue( '7' );
		} );

		// A marker on `window` proves the navigations below are genuinely
		// client-side: a full document reload always executes in a brand
		// new window with no memory of anything set here, while the region
		// swap this fixture's link drives (`actions.navigate()` against a
		// shared `data-wp-router-region` id) never tears down the existing
		// document or its JS runtime.
		await page.evaluate( () => {
			(
				window as unknown as { __wcNavigationSurvivalMarker?: boolean }
			 ).__wcNavigationSurvivalMarker = true;
		} );
		const readMarker = () =>
			page.evaluate(
				() =>
					(
						window as unknown as {
							__wcNavigationSurvivalMarker?: boolean;
						}
					 ).__wcNavigationSurvivalMarker
			);

		await test.step( "navigating client-side to page B never reloads the document; B's unwrapped surface shows page A's unwrapped edit; page A's keyed edit never surfaces there", async () => {
			await page.getByRole( 'link', { name: 'Go to page B' } ).click();

			await expect( page ).toHaveURL( new RegExp( `p=${ pageB.id }$` ) );
			await expect.poll( readMarker ).toBe( true );

			// Page B's own unwrapped surface resolves the identical fallback
			// collection page A's unwrapped surface just wrote to — it
			// shows the edit, not its own untouched default.
			await expect( unwrappedQuantity() ).toHaveValue( '4' );

			// Page B carries no keyed surface of its own, and — more to the
			// point — page A's keyed edit (7) never surfaces here: this
			// unwrapped surface never resolves that collection.
			await expect( keyedQuantity() ).toHaveCount( 0 );
		} );

		await test.step( 'navigating client-side back to page A never reloads the document; both of its surfaces still show their own surviving edits', async () => {
			await page.getByRole( 'link', { name: 'Go to page A' } ).click();

			await expect( page ).toHaveURL( new RegExp( `p=${ pageA.id }$` ) );
			await expect.poll( readMarker ).toBe( true );

			await expect( unwrappedQuantity() ).toHaveValue( '4' );
			await expect( keyedQuantity() ).toHaveValue( '7' );
		} );

		await test.step( "a hard reload — unlike the client-side round trip above — resets both of page A's surfaces to their server-seeded defaults", async () => {
			await page.goto( pageAUrl );

			await expect( unwrappedQuantity() ).toHaveValue( '1' );
			await expect( keyedQuantity() ).toHaveValue( '1' );
		} );
	} );
} );
