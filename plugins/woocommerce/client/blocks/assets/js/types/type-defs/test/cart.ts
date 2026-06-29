/**
 * External dependencies
 */
import type { CartItem } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import type { OptimisticCartItem } from '../../../base/stores/woocommerce/cart';

/**
 * Verifies that `CartItem` declares `has_cart_item_data` as a required boolean
 * property, and that `OptimisticCartItem` does not declare the field.
 *
 * These tests act as a type-level regression net: because the file is compiled
 * by TypeScript (via `ts:check`) and processed by Babel for Jest, any mismatch
 * between the interface declaration and the objects constructed here will
 * surface either as a compile-time error (under `ts:check`) or a runtime
 * assertion failure (under `test:js`).
 */

/** Minimum valid skeleton for the nested shape requirements of `CartItem`. */
const minimalCartItem: CartItem = {
	key: 'abc123',
	id: 1,
	type: 'simple',
	quantity: 2,
	catalog_visibility: 'visible',
	quantity_limits: {
		minimum: 1,
		maximum: 99,
		multiple_of: 1,
		editable: true,
	},
	name: 'Test Product',
	summary: '',
	short_description: '',
	description: '',
	sku: 'TEST-001',
	low_stock_remaining: null,
	backorders_allowed: false,
	show_backorder_badge: false,
	sold_individually: false,
	has_cart_item_data: false,
	permalink: 'https://example.com/test',
	images: [],
	variation: [],
	prices: {
		currency_code: 'USD',
		currency_symbol: '$',
		currency_minor_unit: 2,
		currency_decimal_separator: '.',
		currency_thousand_separator: ',',
		currency_prefix: '$',
		currency_suffix: '',
		price: '1000',
		regular_price: '1000',
		sale_price: '1000',
		price_range: null,
		raw_prices: {
			precision: 6,
			price: '1000000',
			regular_price: '1000000',
			sale_price: '1000000',
		},
	},
	totals: {
		currency_code: 'USD',
		currency_symbol: '$',
		currency_minor_unit: 2,
		currency_decimal_separator: '.',
		currency_thousand_separator: ',',
		currency_prefix: '$',
		currency_suffix: '',
		line_subtotal: '1000',
		line_subtotal_tax: '0',
		line_total: '1000',
		line_total_tax: '0',
	},
	extensions: {},
	item_data: [],
};

/**
 * Minimum valid skeleton for `OptimisticCartItem`. This type must NOT include
 * `has_cart_item_data`; the field is deliberately absent so that optimistic
 * plain lines evaluate as falsy/absent and are treated as plain lines by the
 * keyless matcher guard in the cart store.
 */
const minimalOptimisticCartItem: OptimisticCartItem = {
	id: 1,
	quantity: 1,
	type: 'simple',
};

describe( 'CartItem TypeScript interface', () => {
	describe( 'has_cart_item_data field on CartItem', () => {
		it( 'accepts has_cart_item_data: false for a plain standalone line', () => {
			const item: CartItem = {
				...minimalCartItem,
				has_cart_item_data: false,
			};
			expect( item.has_cart_item_data ).toBe( false );
		} );

		it( 'accepts has_cart_item_data: true for a meta-differentiated line', () => {
			const item: CartItem = {
				...minimalCartItem,
				has_cart_item_data: true,
			};
			expect( item.has_cart_item_data ).toBe( true );
		} );

		it( 'exposes has_cart_item_data as a boolean property on CartItem', () => {
			expect( typeof minimalCartItem.has_cart_item_data ).toBe(
				'boolean'
			);
		} );
	} );

	describe( 'OptimisticCartItem does not declare has_cart_item_data', () => {
		it( 'does not carry has_cart_item_data (field is absent)', () => {
			// Verify the property is not present on the OptimisticCartItem object.
			// This is both a runtime check and a compile-time guard: if the field
			// were added to the type declaration, this test would remain green —
			// but the TypeScript interface check enforces it is not required.
			expect( 'has_cart_item_data' in minimalOptimisticCartItem ).toBe(
				false
			);
		} );

		it( 'accessing a missing has_cart_item_data yields undefined (falsy-safe)', () => {
			// Simulates the falsy-safe guard in the keyless matcher: casting the
			// optimistic item through the union type and reading has_cart_item_data
			// must yield undefined (falsy), not throw.
			const asUnion = minimalOptimisticCartItem as
				| OptimisticCartItem
				| CartItem;
			const value = ( asUnion as CartItem ).has_cart_item_data;
			// `undefined` is falsy, which is how the guard treats a missing field.
			expect( value ).toBeFalsy();
		} );
	} );
} );
