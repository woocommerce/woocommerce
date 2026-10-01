/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import { dispatch } from '@wordpress/data';
import { useShippingData } from '@woocommerce/base-context/hooks';

/**
 * Internal dependencies
 */
import Block from '../block';
import {
	generateShippingPackage,
	generateShippingRate,
} from '../../../../../mocks/shipping-package';

// Read inside the component rather than at module load, so each test can set it.
let mockShippingCostRequiresAddress = false;

jest.mock( '@woocommerce/settings', () => {
	const actualModule = jest.requireActual( '@woocommerce/settings' );
	return {
		...actualModule,
		getSetting: ( name: string, ...rest: unknown[] ) => {
			if ( name === 'localPickupText' ) {
				return 'Pickup text from settings';
			}
			if ( name === 'shippingCostRequiresAddress' ) {
				return mockShippingCostRequiresAddress;
			}
			// Read when the module loads, so a pickup_location rate is seen as collectable
			// and local pickup counts as on.
			if ( name === 'collectableMethodIds' ) {
				return [ 'pickup_location' ];
			}
			if ( name === 'localPickupEnabled' ) {
				return true;
			}
			return actualModule.getSetting( name, ...rest );
		},
	};
} );

jest.mock( '@woocommerce/base-context/hooks', () => ( {
	...jest.requireActual( '@woocommerce/base-context/hooks' ),
	useShippingData: jest.fn(),
} ) );

// The component selects only from the cart store, to decide whether anything is still
// deliverable; answer that one and let any other store resolve for real. The validation store
// the tests drive is read inside cart/utils.ts rather than here, so it works because the mock
// spreads the real module, not because of this fall-through.
let mockPackages: ReturnType< typeof generateShippingPackage >[] = [];

jest.mock( '@wordpress/data', () => {
	const actualModule = jest.requireActual( '@wordpress/data' );
	return {
		...actualModule,
		useSelect: (
			mapSelect: ( select: typeof actualModule.select ) => unknown
		) =>
			mapSelect( ( storeName: { name?: string } | string ) => {
				const name =
					typeof storeName === 'string' ? storeName : storeName?.name;
				if ( name === 'wc/store/cart' ) {
					return { getShippingRates: () => mockPackages };
				}
				return actualModule.select( storeName );
			} ),
	};
} );

const VALIDATION_STORE = 'wc/store/validation';

const deliveryRate = generateShippingRate( {
	rateId: 'flat_rate:1',
	name: 'Flat rate',
	methodID: 'flat_rate',
	price: '500',
	instanceID: 1,
} );

const collectionRate = generateShippingRate( {
	rateId: 'pickup_location:1',
	name: 'Pickup',
	methodID: 'pickup_location',
	price: '0',
	instanceID: 0,
} );

/**
 * Put the store in one shape and render the two options.
 *
 * @param options                     The shopper's situation.
 * @param options.costRequiresAddress Whether the merchant hid costs until an address is entered.
 * @param options.addressIncomplete   Whether the shipping address still has a validation error.
 * @param options.offers              The rates the one package comes back with.
 * @param options.showPrice           Whether the block is set to show prices.
 */
const renderWith = ( {
	costRequiresAddress = false,
	addressIncomplete = false,
	offers,
	showPrice = true,
}: {
	costRequiresAddress?: boolean;
	addressIncomplete?: boolean;
	offers: ReturnType< typeof generateShippingRate >[];
	showPrice?: boolean;
} ) => {
	mockShippingCostRequiresAddress = costRequiresAddress;
	mockPackages = [
		generateShippingPackage( { packageId: 0, shippingRates: offers } ),
	];

	dispatch( VALIDATION_STORE ).clearValidationErrors();
	if ( addressIncomplete ) {
		dispatch( VALIDATION_STORE ).setValidationErrors( {
			shipping_city: { message: 'Enter a city', hidden: true },
		} );
	}

	( useShippingData as jest.Mock ).mockReturnValue( {
		shippingRates: mockPackages,
	} );

	return render(
		<Block
			localPickupText="backup local pickup text"
			onChange={ () => void 0 }
			showIcon={ false }
			showPrice={ showPrice }
			checked={ 'shipping' }
			shippingText="backup shipping text"
		/>
	);
};

afterEach( () => {
	mockShippingCostRequiresAddress = false;
	dispatch( VALIDATION_STORE ).clearValidationErrors();
} );

describe( 'Block', () => {
	it( 'renders the local pickup text from options', () => {
		renderWith( {
			offers: [ deliveryRate, collectionRate ],
			showPrice: false,
		} );

		expect(
			screen.getByText( 'Pickup text from settings' )
		).toBeInTheDocument();
	} );

	it( 'tells the shopper the delivery price needs an address when nothing can be delivered', () => {
		renderWith( { offers: [ collectionRate ] } );

		expect(
			screen.getByText( 'calculated with an address' )
		).toBeInTheDocument();
	} );

	it( 'still shows the delivery price when a rate exists, even with costs hidden and the address incomplete', () => {
		renderWith( {
			costRequiresAddress: true,
			addressIncomplete: true,
			offers: [ deliveryRate, collectionRate ],
		} );

		expect(
			screen.getByRole( 'radio', { name: /backup shipping text/ } )
		).toHaveTextContent( '$5.00' );
		expect(
			screen.queryByText( 'calculated with an address' )
		).not.toBeInTheDocument();
	} );
} );
