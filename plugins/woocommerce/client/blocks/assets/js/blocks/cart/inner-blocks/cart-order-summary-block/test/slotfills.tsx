import { beforeEach, describe, expect, it, vi, type Mock } from 'vitest';
const { mockSlotRender } = vi.hoisted( () => {
	const mockSlotRender = vi.fn( () => <div data-testid="order-meta-slot" /> );
	return {
		mockSlotRender,
	};
} );

/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { useStoreCart } from '@woocommerce/base-context/hooks';

/**
 * Internal dependencies
 */
import { OrderMetaSlotFill } from '../slotfills';
vi.mock( '@woocommerce/base-context/hooks', () => {
	const mock = {
		useStoreCart: vi.fn(),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '@woocommerce/blocks-checkout', () => {
	const MockFill = ( { children }: { children: React.ReactNode } ) => (
		<>{ children }</>
	);
	MockFill.Slot = ( props: Record< string, unknown > ) =>
		mockSlotRender( props );
	return ( ( mock ) => ( {
		default: mock,
		...mock,
	} ) )( {
		ExperimentalOrderMeta: MockFill,
	} );
} );
describe( 'Cart OrderMetaSlotFill', () => {
	beforeEach( () => {
		vi.clearAllMocks();
	} );
	it( 'always renders ExperimentalOrderMeta.Slot with cart context and correct props', () => {
		( useStoreCart as Mock ).mockReturnValue( {
			extensions: {
				'my-ext': true,
			},
			receiveCart: vi.fn(),
			cartTotals: {
				total: '1000',
			},
		} );
		render( <OrderMetaSlotFill /> );
		expect( mockSlotRender ).toHaveBeenCalledWith(
			expect.objectContaining( {
				context: 'woocommerce/cart',
				extensions: {
					'my-ext': true,
				},
				cart: expect.objectContaining( {
					cartTotals: {
						total: '1000',
					},
				} ),
			} )
		);

		// receiveCart should be excluded from cart props.
		const slotProps = mockSlotRender.mock.calls[ 0 ][ 0 ];
		expect( slotProps.cart ).not.toHaveProperty( 'receiveCart' );
	} );
} );
