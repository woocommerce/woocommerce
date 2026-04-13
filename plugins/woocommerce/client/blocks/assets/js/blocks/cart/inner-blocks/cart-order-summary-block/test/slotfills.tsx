/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { useStoreCart } from '@woocommerce/base-context/hooks';

/**
 * Internal dependencies
 */
import { OrderMetaSlotFill } from '../slotfills';

jest.mock( '@woocommerce/base-context/hooks', () => ( {
	useStoreCart: jest.fn(),
} ) );

const mockSlotRender = jest.fn( () => <div data-testid="order-meta-slot" /> );
jest.mock( '@woocommerce/blocks-checkout', () => {
	const MockFill = ( { children }: { children: React.ReactNode } ) => (
		<>{ children }</>
	);
	MockFill.Slot = ( props: Record< string, unknown > ) =>
		mockSlotRender( props );
	return { ExperimentalOrderMeta: MockFill };
} );

describe( 'Cart OrderMetaSlotFill', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'always renders ExperimentalOrderMeta.Slot with cart context and correct props', () => {
		( useStoreCart as jest.Mock ).mockReturnValue( {
			extensions: { 'my-ext': true },
			receiveCart: jest.fn(),
			cartTotals: { total: '1000' },
		} );

		render( <OrderMetaSlotFill /> );

		expect( mockSlotRender ).toHaveBeenCalledWith(
			expect.objectContaining( {
				context: 'woocommerce/cart',
				extensions: { 'my-ext': true },
				cart: expect.objectContaining( {
					cartTotals: { total: '1000' },
				} ),
			} )
		);

		// receiveCart should be excluded from cart props.
		const slotProps = mockSlotRender.mock.calls[ 0 ][ 0 ];
		expect( slotProps.cart ).not.toHaveProperty( 'receiveCart' );
	} );
} );
