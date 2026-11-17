/**
 * External dependencies
 */
import type { SelectedAttributes } from '@woocommerce/stores/woocommerce/cart';

/**
 * Internal dependencies
 */
import type { BackInStockFormStore } from '../frontend';

type ProductSelection = { selectedAttributes?: SelectedAttributes[] };

let mockProductContext: ProductSelection | undefined;
let mockRegisteredStore: BackInStockFormStore;

const mockProductsState = {
	productVariationInContext: null as Record< string, unknown > | null,
	selectedAttributes: undefined as SelectedAttributes[] | undefined,
};

jest.mock(
	'@wordpress/interactivity',
	() => ( {
		store: jest.fn( ( namespace, definition ) => {
			if ( namespace === 'woocommerce/products' ) {
				return { state: mockProductsState };
			}
			mockRegisteredStore = definition;
			return definition;
		} ),
		getContext: jest.fn( ( namespace?: string ) =>
			namespace === 'woocommerce/products'
				? mockProductContext
				: { attribute: 'Size' }
		),
	} ),
	{ virtual: true }
);

jest.mock( '@woocommerce/stores/woocommerce/products', () => ( {} ) );

describe( 'Back in Stock form store', () => {
	beforeEach( () => {
		jest.resetModules();
		mockProductContext = undefined;
		mockProductsState.productVariationInContext = null;
		mockProductsState.selectedAttributes = undefined;

		return import( '../frontend' );
	} );

	it.each( [
		{ variation: null, expected: false },
		{
			variation: { is_in_stock: true, is_purchasable: true },
			expected: false,
		},
		{
			variation: { is_in_stock: false, is_purchasable: true },
			expected: true,
		},
		{
			variation: { is_in_stock: true, is_purchasable: false },
			expected: true,
		},
	] )(
		'is visible ($expected) for variation $variation',
		( { variation, expected } ) => {
			mockProductsState.productVariationInContext = variation;

			expect( mockRegisteredStore.state.isVisible ).toBe( expected );
		}
	);

	it( 'reads the selected attribute value from the products state', () => {
		expect( mockRegisteredStore.state.selectedAttributeValue ).toBe( '' );

		mockProductsState.selectedAttributes = [
			{ attribute: 'Color', value: 'blue' },
			{ attribute: 'Size', value: 'large' },
		];

		expect( mockRegisteredStore.state.selectedAttributeValue ).toBe(
			'large'
		);
	} );

	it( 'prefers the products context over the state', () => {
		mockProductsState.selectedAttributes = [
			{ attribute: 'Size', value: 'large' },
		];
		mockProductContext = {
			selectedAttributes: [ { attribute: 'size', value: 'small' } ],
		};

		expect( mockRegisteredStore.state.selectedAttributeValue ).toBe(
			'small'
		);

		mockProductContext = {};

		expect( mockRegisteredStore.state.selectedAttributeValue ).toBe( '' );
	} );
} );
