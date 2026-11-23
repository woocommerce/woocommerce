/**
 * External dependencies
 */
import { act, render } from '@testing-library/react';
import { createRegistry, RegistryProvider } from '@wordpress/data';
import { CHECKOUT_STORE_KEY } from '@woocommerce/block-data';
import { config as checkoutStoreConfig } from '@woocommerce/block-data/checkout';

/**
 * Internal dependencies
 */
import { ShippingDataProvider } from '../index';
import { useStoreCart } from '../../../../hooks/cart/use-store-cart';
import { useShippingData } from '../../../../hooks/shipping/use-shipping-data';

jest.mock( '@woocommerce/block-data', () => {
	const checkout = jest.requireActual( '@woocommerce/block-data/checkout' );

	return {
		__esModule: true,
		CHECKOUT_STORE_KEY: checkout.CHECKOUT_STORE_KEY,
		checkoutStore: checkout.store,
	};
} );

jest.mock( '../../../../hooks/cart/use-store-cart', () => ( {
	useStoreCart: jest.fn(),
} ) );

jest.mock( '../../../../hooks/shipping/use-shipping-data', () => ( {
	useShippingData: jest.fn(),
} ) );

describe( 'ShippingDataProvider', () => {
	let registry: ReturnType< typeof createRegistry >;

	const mockShippingState = ( {
		isLoadingRates = false,
		isSelectingRate = false,
	} ) => {
		( useStoreCart as jest.Mock ).mockReturnValue( {
			shippingRates: [],
			isLoadingRates,
			cartErrors: [],
		} );
		( useShippingData as jest.Mock ).mockReturnValue( {
			selectedRates: { current: {} },
			isSelectingRate,
		} );
	};

	const tree = () => (
		<RegistryProvider value={ registry }>
			<ShippingDataProvider>
				<div />
			</ShippingDataProvider>
		</RegistryProvider>
	);

	const isCalculating = () =>
		registry.select( CHECKOUT_STORE_KEY ).isCalculating();

	beforeEach( () => {
		registry = createRegistry();
		registry.registerStore( CHECKOUT_STORE_KEY, checkoutStoreConfig );
	} );

	describe( 'checkout calculating count', () => {
		it( 'keeps an existing hold when mounted with nothing in flight', () => {
			registry
				.dispatch( CHECKOUT_STORE_KEY )
				.__internalStartCalculation();
			mockShippingState( {} );

			render( tree() );

			expect( isCalculating() ).toBe( true );
		} );

		it.each( [ 'isLoadingRates', 'isSelectingRate' ] )(
			'releases only its own hold once %s turns false',
			( flag ) => {
				registry
					.dispatch( CHECKOUT_STORE_KEY )
					.__internalStartCalculation();
				mockShippingState( { [ flag ]: true } );
				const { rerender } = render( tree() );

				mockShippingState( {} );
				rerender( tree() );

				expect( isCalculating() ).toBe( true );

				act( () => {
					registry
						.dispatch( CHECKOUT_STORE_KEY )
						.__internalFinishCalculation();
				} );

				expect( isCalculating() ).toBe( false );
			}
		);

		it.each( [ 'isLoadingRates', 'isSelectingRate' ] )(
			'releases its hold when unmounted while %s',
			( flag ) => {
				mockShippingState( { [ flag ]: true } );
				const { unmount } = render( tree() );

				expect( isCalculating() ).toBe( true );

				unmount();

				expect( isCalculating() ).toBe( false );
			}
		);
	} );
} );
