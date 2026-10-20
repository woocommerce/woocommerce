/**
 * External dependencies
 */
import { test, expect, enableTestPlugin } from '@woocommerce/e2e-utils';
import type { Page } from '@playwright/test';

/**
 * Internal dependencies
 */
import { REGULAR_PRICED_PRODUCT_NAME } from '../checkout/constants';

test.describe( 'Mini-Cart Block (item data)', () => {
	test.use( {
		storageState: {
			origins: [],
			cookies: [],
		},
	} );

	test.beforeEach( async ( { context } ) => {
		await enableTestPlugin( context, 'item-data-display' );
	} );

	test( 'should render plain text item data', async ( {
		page,
		frontendUtils,
		miniCartUtils,
	} ) => {
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		await miniCartUtils.openMiniCart();

		const dialog = page.getByRole( 'dialog' );
		await expect( dialog ).toBeVisible();

		// Verify the plain text name and value are rendered.
		await expect(
			dialog.locator( '.wc-block-components-product-details__name' )
		).toContainText( [ 'Gift Message' ] );
		await expect(
			dialog.locator( '.wc-block-components-product-details__value' )
		).toContainText( [ 'Happy Birthday!' ] );
	} );

	test( 'should use display field value when present', async ( {
		page,
		frontendUtils,
		miniCartUtils,
	} ) => {
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		await miniCartUtils.openMiniCart();

		const dialog = page.getByRole( 'dialog' );
		await expect( dialog ).toBeVisible();

		// The display field contains "<em>Best Wishes</em>".
		// Verify the <em> tag is rendered as an actual HTML element.
		const engravingValue = dialog
			.locator( '.wc-block-components-product-details__value' )
			.filter( { hasText: 'Best Wishes' } );
		await expect( engravingValue ).toBeVisible();
		await expect( engravingValue.locator( 'em' ) ).toBeVisible();
		await expect( engravingValue.locator( 'em' ) ).toHaveText(
			'Best Wishes'
		);
	} );

	test( 'should decode HTML entities in item data values', async ( {
		page,
		frontendUtils,
		miniCartUtils,
	} ) => {
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		await miniCartUtils.openMiniCart();

		const dialog = page.getByRole( 'dialog' );
		await expect( dialog ).toBeVisible();

		// The value "1 &lt; 2" should display as "1 < 2"
		// (entity decoded), not as literal "1 &lt; 2".
		const sizeValue = dialog
			.locator( '.wc-block-components-product-details__value' )
			.filter( { hasText: '1' } );
		await expect( sizeValue ).toBeVisible();
		await expect( sizeValue ).toContainText( '1 < 2' );
	} );

	test( 'should not render entity-encoded HTML tags as DOM elements', async ( {
		page,
		frontendUtils,
		miniCartUtils,
	} ) => {
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		await miniCartUtils.openMiniCart();

		const dialog = page.getByRole( 'dialog' );
		await expect( dialog ).toBeVisible();

		const noteValue = dialog
			.locator( '.wc-block-components-product-details__value' )
			.filter( { hasText: 'important' } );
		await expect( noteValue ).toBeVisible();
		await expect( noteValue.locator( 'b' ) ).toHaveCount( 0 );
	} );

	test( 'should show a separator between item-data entries but not after the last one', async ( {
		page,
		frontendUtils,
		miniCartUtils,
	} ) => {
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		await miniCartUtils.openMiniCart();

		const dialog = page.getByRole( 'dialog' );
		await expect( dialog ).toBeVisible();

		// The fixture plugin injects 4 well-formed entries (Gift Message,
		// Engraving, Size, Note) onto the cart item. Confirm all 4 rendered
		// before counting separators, so a count mismatch below can only be
		// attributed to separator placement, not to a missing entry.
		const entryNames = dialog.locator(
			'.wc-block-components-product-details__name'
		);
		await expect( entryNames ).toHaveCount( 4 );

		// Each entry's trailing separator is a `span[aria-hidden="true"]`;
		// the store getter that drives its `hidden` binding removes the
		// `hidden` attribute exactly for the entries that should show a
		// separator. Count only the ones that are actually visible (not
		// `hidden`) — using `textContent` with a regex would also match the
		// text of `hidden` separators and give a false pass/fail.
		const visibleSeparators = dialog.locator(
			'.wc-block-components-product-details span[aria-hidden="true"]:not([hidden])'
		);
		await expect( visibleSeparators ).toHaveCount( 3 );
	} );
} );

/**
 * Registers page-error and console-error listeners for a defect scenario
 * (trailing malformed/hidden item-data entries). Must be called before
 * navigating so nothing that happens during the mini-cart's render is
 * missed — mirrors the listener setup used by the "no client-side error"
 * test in mini-cart.block_theme.spec.ts.
 *
 * @param {Page} page Playwright page to observe.
 * @return {{pageErrors: string[], consoleErrors: string[]}} Arrays that
 *                                                            accumulate as
 *                                                            errors fire.
 */
const trackErrors = ( page: Page ) => {
	const pageErrors: string[] = [];
	const consoleErrors: string[] = [];

	page.on( 'pageerror', ( error ) => {
		pageErrors.push( error.message );
	} );
	page.on( 'console', ( message ) => {
		// Exclude the browser's own "failed to load resource" network
		// diagnostics: they are not JS errors raised by page code (no
		// `console.error` call is involved) and can fire for reasons
		// entirely unrelated to the item-data/separator logic under test.
		if (
			message.type() === 'error' &&
			! message.text().includes( 'Failed to load resource' )
		) {
			consoleErrors.push( message.text() );
		}
	} );

	return { pageErrors, consoleErrors };
};

test.describe( 'Mini-Cart Block (item data - malformed trailing entries)', () => {
	test.use( {
		storageState: {
			origins: [],
			cookies: [],
		},
	} );

	test.beforeEach( async ( { context } ) => {
		await enableTestPlugin( context, 'item-data-display-malformed' );
	} );

	test( 'should render no client-side error and no trailing separator when the item data ends with a single malformed entry', async ( {
		page,
		frontendUtils,
		miniCartUtils,
	} ) => {
		const { pageErrors, consoleErrors } = trackErrors( page );

		await frontendUtils.goToShop();
		// Quantity 1 selects the fixture's [visible, malformed] scenario.
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		await miniCartUtils.openMiniCart();

		const dialog = page.getByRole( 'dialog' );
		await expect( dialog ).toBeVisible();

		// Both entries render into the DOM (the malformed one hidden);
		// confirm the DOM count before checking visibility, so a mismatch
		// below can only be attributed to visibility/separator logic, not
		// to a missing entry.
		const allNames = dialog.locator(
			'.wc-block-components-product-details__name'
		);
		await expect( allNames ).toHaveCount( 2 );

		// The malformed entry renders no visible name/value: only the
		// well-formed "Gift Message" entry is visible. Using `:visible`
		// filters out elements hidden via the `hidden` attribute, unlike a
		// plain `textContent` check which would also match hidden text.
		const visibleNames = dialog.locator(
			'.wc-block-components-product-details__name:visible'
		);
		await expect( visibleNames ).toHaveCount( 1 );
		await expect( visibleNames ).toContainText( [ 'Gift Message' ] );

		// The last (and only) visible entry shows no trailing separator,
		// and no stray separator appears anywhere else in the list. Count
		// only separators that are actually visible. `:visible` accounts
		// for ancestor visibility, so a malformed/hidden entry's separator
		// span — whose own `hidden` attribute is absent because it is not
		// the last *visible* entry, but whose entry wrapper is hidden — is
		// correctly excluded. A plain `:not([hidden])` attribute check
		// would miss the hidden ancestor and a `textContent` regex would
		// match hidden text.
		const visibleSeparators = dialog.locator(
			'.wc-block-components-product-details span[aria-hidden="true"]:visible'
		);
		await expect( visibleSeparators ).toHaveCount( 0 );

		expect( pageErrors ).toEqual( [] );
		expect( consoleErrors ).toEqual( [] );
	} );

	test( 'should render no client-side error and no trailing separator when the item data ends with two consecutive malformed entries', async ( {
		page,
		frontendUtils,
		miniCartUtils,
	} ) => {
		const { pageErrors, consoleErrors } = trackErrors( page );

		await frontendUtils.goToShop();
		// Adding the same product twice merges into a single cart line with
		// quantity 2, which selects the fixture's
		// [visible, malformed, malformed] scenario.
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		await miniCartUtils.openMiniCart();

		const dialog = page.getByRole( 'dialog' );
		await expect( dialog ).toBeVisible();

		const allNames = dialog.locator(
			'.wc-block-components-product-details__name'
		);
		await expect( allNames ).toHaveCount( 3 );

		const visibleNames = dialog.locator(
			'.wc-block-components-product-details__name:visible'
		);
		await expect( visibleNames ).toHaveCount( 1 );
		await expect( visibleNames ).toContainText( [ 'Gift Message' ] );

		// Count only separators that are actually visible. `:visible`
		// accounts for ancestor visibility, so a malformed/hidden entry's
		// separator span (hidden via its entry wrapper, not its own
		// attribute) is correctly excluded — unlike `:not([hidden])`.
		const visibleSeparators = dialog.locator(
			'.wc-block-components-product-details span[aria-hidden="true"]:visible'
		);
		await expect( visibleSeparators ).toHaveCount( 0 );

		expect( pageErrors ).toEqual( [] );
		expect( consoleErrors ).toEqual( [] );
	} );
} );

test.describe( 'Mini-Cart Block (item data - hidden trailing entries)', () => {
	test.use( {
		storageState: {
			origins: [],
			cookies: [],
		},
	} );

	test.beforeEach( async ( { context } ) => {
		await enableTestPlugin( context, 'item-data-display-hidden' );
	} );

	test( 'should render no client-side error and no trailing separator when the item data ends with a single explicitly-hidden entry', async ( {
		page,
		frontendUtils,
		miniCartUtils,
	} ) => {
		const { pageErrors, consoleErrors } = trackErrors( page );

		await frontendUtils.goToShop();
		// Quantity 1 selects the fixture's [visible, hidden] scenario.
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		await miniCartUtils.openMiniCart();

		const dialog = page.getByRole( 'dialog' );
		await expect( dialog ).toBeVisible();

		const allNames = dialog.locator(
			'.wc-block-components-product-details__name'
		);
		await expect( allNames ).toHaveCount( 2 );

		// The explicitly-hidden entry renders no visible name/value ("Secret"
		// never appears among the visible entries): only the well-formed
		// "Gift Message" entry is visible.
		const visibleNames = dialog.locator(
			'.wc-block-components-product-details__name:visible'
		);
		await expect( visibleNames ).toHaveCount( 1 );
		await expect( visibleNames ).toContainText( [ 'Gift Message' ] );

		// Count only separators that are actually visible. `:visible`
		// accounts for ancestor visibility, so a malformed/hidden entry's
		// separator span (hidden via its entry wrapper, not its own
		// attribute) is correctly excluded — unlike `:not([hidden])`.
		const visibleSeparators = dialog.locator(
			'.wc-block-components-product-details span[aria-hidden="true"]:visible'
		);
		await expect( visibleSeparators ).toHaveCount( 0 );

		expect( pageErrors ).toEqual( [] );
		expect( consoleErrors ).toEqual( [] );
	} );

	test( 'should render no client-side error and no trailing separator when the item data ends with two consecutive explicitly-hidden entries', async ( {
		page,
		frontendUtils,
		miniCartUtils,
	} ) => {
		const { pageErrors, consoleErrors } = trackErrors( page );

		await frontendUtils.goToShop();
		// Adding the same product twice merges into a single cart line with
		// quantity 2, which selects the fixture's
		// [visible, hidden, hidden] scenario.
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		await miniCartUtils.openMiniCart();

		const dialog = page.getByRole( 'dialog' );
		await expect( dialog ).toBeVisible();

		const allNames = dialog.locator(
			'.wc-block-components-product-details__name'
		);
		await expect( allNames ).toHaveCount( 3 );

		const visibleNames = dialog.locator(
			'.wc-block-components-product-details__name:visible'
		);
		await expect( visibleNames ).toHaveCount( 1 );
		await expect( visibleNames ).toContainText( [ 'Gift Message' ] );

		// Count only separators that are actually visible. `:visible`
		// accounts for ancestor visibility, so a malformed/hidden entry's
		// separator span (hidden via its entry wrapper, not its own
		// attribute) is correctly excluded — unlike `:not([hidden])`.
		const visibleSeparators = dialog.locator(
			'.wc-block-components-product-details span[aria-hidden="true"]:visible'
		);
		await expect( visibleSeparators ).toHaveCount( 0 );

		expect( pageErrors ).toEqual( [] );
		expect( consoleErrors ).toEqual( [] );
	} );
} );

test.describe( 'Mini-Cart Block (item data - mixed trailing entries)', () => {
	test.use( {
		storageState: {
			origins: [],
			cookies: [],
		},
	} );

	test.beforeEach( async ( { context } ) => {
		await enableTestPlugin( context, 'item-data-display-mixed' );
	} );

	test( 'should show no trailing separator when the item data ends with a malformed entry followed by a hidden entry', async ( {
		page,
		frontendUtils,
		miniCartUtils,
	} ) => {
		const { pageErrors, consoleErrors } = trackErrors( page );

		await frontendUtils.goToShop();
		// Quantity 1 selects the fixture's
		// [visible, malformed, hidden] scenario.
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		await miniCartUtils.openMiniCart();

		const dialog = page.getByRole( 'dialog' );
		await expect( dialog ).toBeVisible();

		const allNames = dialog.locator(
			'.wc-block-components-product-details__name'
		);
		await expect( allNames ).toHaveCount( 3 );

		const visibleNames = dialog.locator(
			'.wc-block-components-product-details__name:visible'
		);
		await expect( visibleNames ).toHaveCount( 1 );
		await expect( visibleNames ).toContainText( [ 'Gift Message' ] );

		// Count only separators that are actually visible. `:visible`
		// accounts for ancestor visibility, so a malformed/hidden entry's
		// separator span (hidden via its entry wrapper, not its own
		// attribute) is correctly excluded — unlike `:not([hidden])`.
		const visibleSeparators = dialog.locator(
			'.wc-block-components-product-details span[aria-hidden="true"]:visible'
		);
		await expect( visibleSeparators ).toHaveCount( 0 );

		expect( pageErrors ).toEqual( [] );
		expect( consoleErrors ).toEqual( [] );
	} );

	test( 'should show no trailing separator when the item data ends with a hidden entry followed by a malformed entry', async ( {
		page,
		frontendUtils,
		miniCartUtils,
	} ) => {
		const { pageErrors, consoleErrors } = trackErrors( page );

		await frontendUtils.goToShop();
		// Adding the same product twice merges into a single cart line with
		// quantity 2, which selects the fixture's
		// [visible, hidden, malformed] scenario (the reverse order).
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		await miniCartUtils.openMiniCart();

		const dialog = page.getByRole( 'dialog' );
		await expect( dialog ).toBeVisible();

		const allNames = dialog.locator(
			'.wc-block-components-product-details__name'
		);
		await expect( allNames ).toHaveCount( 3 );

		const visibleNames = dialog.locator(
			'.wc-block-components-product-details__name:visible'
		);
		await expect( visibleNames ).toHaveCount( 1 );
		await expect( visibleNames ).toContainText( [ 'Gift Message' ] );

		// Count only separators that are actually visible. `:visible`
		// accounts for ancestor visibility, so a malformed/hidden entry's
		// separator span (hidden via its entry wrapper, not its own
		// attribute) is correctly excluded — unlike `:not([hidden])`.
		const visibleSeparators = dialog.locator(
			'.wc-block-components-product-details span[aria-hidden="true"]:visible'
		);
		await expect( visibleSeparators ).toHaveCount( 0 );

		expect( pageErrors ).toEqual( [] );
		expect( consoleErrors ).toEqual( [] );
	} );
} );
