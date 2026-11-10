/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import type { CartItem } from '@woocommerce/types';
import { getSetting, getSettingWithCoercion } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import CartLineItemRow from '../cart-line-item-row';

jest.mock( '@woocommerce/settings', () => ( {
	...jest.requireActual( '@woocommerce/settings' ),
	// Default implementations return the provided defaultValue so module-load
	// time calls (e.g. `getSetting( 'wcBlocksConfig', { pluginUrl: '', ... } )`
	// in the settings constants module) still receive a usable shape. Per-test
	// overrides via `setSettings` below replace these implementations.
	getSetting: jest.fn(
		( _key: string, defaultValue: unknown ) => defaultValue
	),
	getSettingWithCoercion: jest.fn(
		( _key: string, defaultValue: unknown ) => defaultValue
	),
} ) );

jest.mock( '@woocommerce/base-context/hooks', () => ( {
	...jest.requireActual( '@woocommerce/base-context/hooks' ),
	useStoreCartItemQuantity: jest.fn( () => ( {
		quantity: 1,
		setItemQuantity: jest.fn(),
		removeItem: jest.fn(),
		isPendingDelete: false,
	} ) ),
	useStoreEvents: jest.fn( () => ( {
		dispatchStoreEvent: jest.fn(),
	} ) ),
	useStoreCart: jest.fn( () => ( {
		receiveCart: jest.fn(),
		cartItems: [],
	} ) ),
	useSaveForLater: jest.fn( () => ( {
		saveForLater: jest.fn(),
		isSaving: false,
	} ) ),
} ) );

jest.mock( '@woocommerce/blocks-checkout', () => ( {
	...jest.requireActual( '@woocommerce/blocks-checkout' ),
	applyCheckoutFilter: jest.fn( ( { defaultValue } ) => defaultValue ),
	productPriceValidation: jest.fn(),
} ) );

const mockGetSetting = getSetting as jest.Mock;
const mockGetSettingWithCoercion = getSettingWithCoercion as jest.Mock;

/**
 * Minimal CartItem shape — only the fields the row actually reads. Using an
 * inline fixture instead of `previewCart` keeps this test free of the
 * preview module's transitive imports (which expect `wcBlocksConfig` to be
 * populated at module-load time).
 */
const buildLineItem = (): CartItem =>
	( {
		key: 'test-key',
		id: 1,
		type: 'simple',
		quantity: 1,
		catalog_visibility: 'visible',
		name: 'Test product',
		short_description: '',
		description: '',
		sku: 'test-sku',
		permalink: 'https://example.org',
		low_stock_remaining: null,
		backorders_allowed: false,
		show_backorder_badge: false,
		sold_individually: false,
		quantity_limits: {
			minimum: 1,
			maximum: 99,
			multiple_of: 1,
			editable: true,
		},
		images: [],
		variation: [],
		item_data: [],
		prices: {
			currency_code: 'USD',
			currency_minor_unit: 2,
			currency_symbol: '$',
			currency_prefix: '$',
			currency_suffix: '',
			currency_decimal_separator: '.',
			currency_thousand_separator: ',',
			price: '1000',
			regular_price: '1000',
			sale_price: '1000',
			price_range: null,
			raw_prices: {
				precision: 6,
				price: '10000000',
				regular_price: '10000000',
				sale_price: '10000000',
			},
		},
		totals: {
			currency_code: 'USD',
			currency_minor_unit: 2,
			currency_symbol: '$',
			currency_prefix: '$',
			currency_suffix: '',
			currency_decimal_separator: '.',
			currency_thousand_separator: ',',
			line_subtotal: '1000',
			line_subtotal_tax: '0',
			line_total: '1000',
			line_total_tax: '0',
		},
		extensions: {},
	} ) as unknown as CartItem;

/**
 * Configure `getSetting` and `getSettingWithCoercion` mocks for the three
 * signals the cart row reads when deciding whether to render the
 * "Save for later" button. Any setting not explicitly provided falls back to
 * the default supplied by the caller (same behaviour as the production
 * helpers).
 */
const setSettings = ( {
	currentUserId = 0,
	experimentalCartSaveForLater,
	cartPageHasSavedForLater,
}: {
	currentUserId?: number;
	experimentalCartSaveForLater?: boolean;
	cartPageHasSavedForLater?: boolean;
} ) => {
	mockGetSetting.mockImplementation(
		( key: string, defaultValue: unknown ) => {
			if ( key === 'currentUserId' ) {
				return currentUserId;
			}
			return defaultValue;
		}
	);
	mockGetSettingWithCoercion.mockImplementation(
		( key: string, defaultValue: unknown ) => {
			if (
				key === 'experimentalCartSaveForLater' &&
				experimentalCartSaveForLater !== undefined
			) {
				return experimentalCartSaveForLater;
			}
			if (
				key === 'cartPageHasSavedForLater' &&
				cartPageHasSavedForLater !== undefined
			) {
				return cartPageHasSavedForLater;
			}
			return defaultValue;
		}
	);
};

const renderRow = () => {
	// Wrap in a table so the row's <td>s render in a valid context.
	return render(
		<table>
			<tbody>
				<CartLineItemRow lineItem={ buildLineItem() } />
			</tbody>
		</table>
	);
};

describe( 'CartLineItemRow — Save for later visibility', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'shows the Save for later button when user is logged in, feature is enabled, and the page has a saved-for-later block', () => {
		setSettings( {
			currentUserId: 42,
			experimentalCartSaveForLater: true,
			cartPageHasSavedForLater: true,
		} );

		renderRow();

		expect(
			screen.getByRole( 'button', { name: /save for later/i } )
		).toBeInTheDocument();
	} );

	it( 'hides the Save for later button when the user is logged out', () => {
		setSettings( {
			currentUserId: 0,
			experimentalCartSaveForLater: true,
			cartPageHasSavedForLater: true,
		} );

		renderRow();

		expect(
			screen.queryByRole( 'button', { name: /save for later/i } )
		).not.toBeInTheDocument();
	} );

	it( 'hides the Save for later button when the page has no saved-for-later block', () => {
		setSettings( {
			currentUserId: 42,
			experimentalCartSaveForLater: true,
			cartPageHasSavedForLater: false,
		} );

		renderRow();

		expect(
			screen.queryByRole( 'button', { name: /save for later/i } )
		).not.toBeInTheDocument();
	} );

	it( 'hides the Save for later button when the feature flag is disabled', () => {
		setSettings( {
			currentUserId: 42,
			experimentalCartSaveForLater: false,
			cartPageHasSavedForLater: true,
		} );

		renderRow();

		expect(
			screen.queryByRole( 'button', { name: /save for later/i } )
		).not.toBeInTheDocument();
	} );

	it( 'hides the Save for later button when none of the signals are set (defaults)', () => {
		setSettings( {} );

		renderRow();

		expect(
			screen.queryByRole( 'button', { name: /save for later/i } )
		).not.toBeInTheDocument();
	} );
} );
