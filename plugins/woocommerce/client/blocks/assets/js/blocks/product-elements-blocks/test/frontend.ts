import type { ProductResponseItem } from '@woocommerce/types';

let mockState: {
	saleBadgeText: string | null;
	saleBadgeScreenReaderText: string;
	isSaleBadgeHidden: boolean;
};
const mockProductsState = {
	productVariationInContext: null as ProductResponseItem | null,
};
const mockContext = {
	saleBadgeText: 'Up to 50%',
	saleBadgeScreenReaderText: 'Product on sale: Up to 50%',
	badgeContent: 'percentage',
	prefix: 'Save ',
	suffix: ' off',
};

jest.mock( '@woocommerce/stores/woocommerce/products', () => ( {} ) );
jest.mock(
	'@wordpress/interactivity',
	() => ( {
		store: ( name: string, definition: { state: typeof mockState } ) => {
			if ( name === 'woocommerce/products' ) {
				return { state: mockProductsState };
			}
			mockState = definition.state;
			return definition;
		},
		getContext: () => mockContext,
		getConfig: () => ( {
			saleBadgePercentageTemplate: '%s%',
			saleBadgeScreenReaderTemplate: 'Product on sale: %s',
		} ),
	} ),
	{ virtual: true }
);

beforeAll( async () => {
	await import( '../frontend' );
} );

it( 'derives selected labels from product prices, hides nonsale variations, and restores the parent on reset', () => {
	mockProductsState.productVariationInContext = {
		on_sale: true,
		prices: {
			regular_price: '1000',
			price: '750',
			currency_code: 'USD',
			currency_symbol: '$',
			currency_minor_unit: 2,
			currency_decimal_separator: '.',
			currency_thousand_separator: ',',
			currency_prefix: '$',
			currency_suffix: '',
		},
	} as ProductResponseItem;
	expect( mockState.saleBadgeText ).toBe( 'Save 25% off' );
	expect( mockState.saleBadgeScreenReaderText ).toBe(
		'Product on sale: Save 25% off'
	);
	mockContext.badgeContent = 'amount';
	expect( mockState.saleBadgeText ).toBe( 'Save $2.50 off' );
	mockContext.badgeContent = 'text';
	mockContext.saleBadgeText = 'Offer';
	expect( mockState.saleBadgeText ).toBe( 'Offer' );

	mockProductsState.productVariationInContext.on_sale = false;
	expect( mockState.isSaleBadgeHidden ).toBe( true );
	mockProductsState.productVariationInContext = null;
	mockContext.saleBadgeText = 'Up to 50%';
	expect( mockState.isSaleBadgeHidden ).toBe( false );
	expect( mockState.saleBadgeText ).toBe( 'Up to 50%' );
	expect( mockState.saleBadgeScreenReaderText ).toBe(
		'Product on sale: Up to 50%'
	);
} );
