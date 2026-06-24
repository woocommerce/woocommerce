/**
 * External dependencies
 */
import { Page, Locator } from '@playwright/test';

/**
 * Activation slug of the cart-line-identity helper plugin
 * (`test-plugins/blocks/cart-line-identity.php`). When active, an add-to-cart
 * request that carries the `cart_line_identity_marker` flag receives a unique
 * `cart_item_data` entry, so core's `generate_cart_id` mints a distinct cart
 * line for the same product id — a stand-in for a bundle child / booking /
 * add-on / recipient (meta-differentiated) line.
 */
export const CART_LINE_IDENTITY_PLUGIN =
	'woocommerce-blocks-test-cart-line-identity';

/**
 * Request flag the helper plugin reads to mark an add as a meta line.
 *
 * Kept identical to `CART_LINE_IDENTITY_FLAG` in the helper plugin.
 */
export const CART_LINE_IDENTITY_FLAG = 'cart_line_identity_marker';

/**
 * Sample-data product used as "product X" throughout the simple-product flows.
 *
 * Beanie is a simple product present on the first shop page, so its
 * ProductButton is clickable from `/shop` without pagination, and it is not a
 * prefix of another product on that page that would make an exact-label match
 * ambiguous (we still scope clicks to its `li.post-<id>` wrapper to be safe).
 */
export const PRODUCT_X = {
	id: 10,
	name: 'Beanie',
} as const;

/**
 * Seeds "product X is in the cart as a single meta-differentiated line".
 *
 * Navigates to a flagged legacy add-to-cart URL. The helper plugin attaches a
 * unique `cart_item_data` marker to this add, so it lands as a cart line whose
 * cart id differs from a plain (unflagged) add of the same product. The browser
 * shares the same WooCommerce session cookie that the Store API add path uses,
 * so a later keyless add of X operates against this same cart and is resolved
 * by the server as a separate standalone line.
 *
 * The helper plugin must already be active (activate it in `beforeEach`).
 *
 * @param page      The Playwright page.
 * @param productId The product id to seed as a meta line.
 * @param marker    The marker value (defaults to the helper's own default).
 */
export const seedMetaLine = async (
	page: Page,
	productId: number,
	marker?: string
) => {
	const markerValue = marker ?? 'meta-line';
	await page.goto(
		`/?add-to-cart=${ productId }&${ CART_LINE_IDENTITY_FLAG }=${ markerValue }`
	);
};

/**
 * The ProductButton for a given product on the shop archive.
 *
 * The shop renders one list item per product (`li.post-<id>`); scoping the
 * button to that wrapper targets exactly one product's Add to cart control even
 * when another product's name shares a prefix.
 *
 * @param page      The Playwright page (must be on the shop archive).
 * @param productId The product id whose button to return.
 */
export const productButton = ( page: Page, productId: number ): Locator =>
	page.locator( `li.post-${ productId }` ).getByRole( 'button' );

/**
 * All cart-line quantity inputs for a product on the blocks Cart page.
 *
 * Every cart line for the product (standalone or meta) renders a quantity input
 * labelled `Quantity of <name> in your cart.`, so the returned locator's
 * `count()` is the number of distinct cart lines for that product and each
 * `nth(i)` exposes a line's quantity via `inputValue()`/`toHaveValue()`.
 *
 * @param page        The Playwright page (must be on the Cart page).
 * @param productName The product display name.
 */
export const cartLineQuantities = (
	page: Page,
	productName: string
): Locator => page.getByLabel( `Quantity of ${ productName } in your cart.` );

/**
 * Reads each cart line's quantity for a product, sorted ascending.
 *
 * Sorting makes the assertion order-independent: the cart can render the meta
 * line and the standalone line in either order, but `[ 1, 1 ]` or `[ 1, 2 ]`
 * uniquely describes the outcome regardless of which row came first.
 *
 * @param page        The Playwright page (must be on the Cart page).
 * @param productName The product display name.
 * @return The quantities of every cart line for the product, ascending.
 */
export const readCartLineQuantities = async (
	page: Page,
	productName: string
): Promise< number[] > => {
	const inputs = cartLineQuantities( page, productName );
	const count = await inputs.count();
	const quantities: number[] = [];
	for ( let i = 0; i < count; i++ ) {
		quantities.push( Number( await inputs.nth( i ).inputValue() ) );
	}
	return quantities.sort( ( a, b ) => a - b );
};

/**
 * All cart-line rows for a product on the blocks Cart page.
 *
 * Unlike {@link cartLineQuantities}, this counts the cart-item rows themselves
 * (`.wc-block-cart-items__row`), so it works even for a product whose quantity
 * input is absent — a sold-individually product renders a line with no quantity
 * stepper. The returned locator's `count()` is the number of cart lines for the
 * product, regardless of whether each line exposes an editable quantity.
 *
 * @param page        The Playwright page (must be on the Cart page).
 * @param productName The product display name.
 */
export const cartLineRows = ( page: Page, productName: string ): Locator =>
	page.locator( '.wc-block-cart-items__row', {
		hasText: new RegExp( productName ),
	} );
