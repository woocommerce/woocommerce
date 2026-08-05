/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import { useStoreCart } from '@woocommerce/base-context';

/**
 * Internal dependencies
 */
import ShippingRatesControl from '..';

jest.mock( '@woocommerce/base-context', () => ( {
	useStoreCart: jest.fn(),
	useEditorContext: jest.fn( () => ( { isEditor: false } ) ),
	useShippingData: jest.fn( () => ( {
		hasSelectedLocalPickup: false,
		selectedRates: {},
	} ) ),
} ) );

jest.mock( '@woocommerce/base-hooks', () => ( {
	usePrevious: jest.fn(),
} ) );

const mockSlotRender = jest.fn( () => <div data-testid="shipping-slot" /> );
jest.mock( '@woocommerce/blocks-checkout', () => {
	const MockFill = ( { children }: { children: React.ReactNode } ) => (
		<>{ children }</>
	);
	MockFill.Slot = ( props: Record< string, unknown > ) =>
		mockSlotRender( props );
	return { ExperimentalOrderShippingPackages: MockFill };
} );

const defaultProps = {
	shippingRates: [],
	isLoadingRates: false,
	className: 'test-class',
	collapsible: false,
	showItems: false,
	noResultsMessage: <span>No rates</span>,
	renderOption: jest.fn(),
	context: 'woocommerce/checkout',
};

describe( 'ShippingRatesControl slot rendering', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		( useStoreCart as jest.Mock ).mockReturnValue( {
			extensions: { 'ship-ext': true },
			receiveCart: jest.fn(),
			cartTotals: {},
		} );
	} );

	it( 'renders ExperimentalOrderShippingPackages.Slot with correct props when not loading', () => {
		render( <ShippingRatesControl { ...defaultProps } /> );

		expect( screen.getByTestId( 'shipping-slot' ) ).toBeInTheDocument();

		expect( mockSlotRender ).toHaveBeenCalledWith(
			expect.objectContaining( {
				context: 'woocommerce/checkout',
				extensions: { 'ship-ext': true },
				collapsible: false,
				showItems: false,
			} )
		);

		const slotProps = mockSlotRender.mock.calls[ 0 ][ 0 ];
		expect( slotProps.cart ).not.toHaveProperty( 'receiveCart' );
		expect( slotProps ).toHaveProperty( 'components' );
		expect( slotProps ).toHaveProperty( 'renderOption' );
		expect( slotProps ).toHaveProperty( 'noResultsMessage' );
	} );

	it( 'does not render the slot when rates are loading', () => {
		render(
			<ShippingRatesControl { ...defaultProps } isLoadingRates={ true } />
		);

		expect(
			screen.queryByTestId( 'shipping-slot' )
		).not.toBeInTheDocument();
		expect( mockSlotRender ).not.toHaveBeenCalled();
	} );
} );
