/**
 * External dependencies
 */
import type { SelectedAttributes } from '@woocommerce/stores/woocommerce';

/**
 * Internal dependencies
 */
import type { BackInStockFormStore } from '../frontend';

type BlockContext = { selectedAttributes?: SelectedAttributes[] };

let mockBlockContext: BlockContext | undefined;
let mockRegisteredStore: BackInStockFormStore;

const mockProductsState = {
	productVariationInContext: null as Record< string, unknown > | null,
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
			namespace === 'woocommerce/add-to-cart-with-options'
				? mockBlockContext
				: { attribute: 'Size' }
		),
	} ),
	{ virtual: true }
);

jest.mock( '@woocommerce/stores/woocommerce/products', () => ( {} ) );

describe( 'Back in Stock form store', () => {
	beforeEach( () => {
		jest.resetModules();
		mockBlockContext = undefined;
		mockProductsState.productVariationInContext = null;

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

	it( 'reads the selected attribute value from the Add to Cart + Options context', () => {
		expect( mockRegisteredStore.state.selectedAttributeValue ).toBe( '' );

		mockBlockContext = {
			selectedAttributes: [
				{ attribute: 'Color', value: 'blue' },
				{ attribute: 'size', value: 'large' },
			],
		};

		expect( mockRegisteredStore.state.selectedAttributeValue ).toBe(
			'large'
		);

		mockBlockContext = {};

		expect( mockRegisteredStore.state.selectedAttributeValue ).toBe( '' );
	} );
} );
