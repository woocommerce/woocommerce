/**
 * External dependencies
 */
import { render, screen, act } from '@testing-library/react';
import { dispatch } from '@wordpress/data';
import { cartStore, validationStore } from '@woocommerce/block-data';

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
 * Seed the cart store with the given rates and render the two options.
 *
 * Both the component's own cart selector and the real useShippingData read these seeded rates.
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

	act( () => {
		dispatch( cartStore ).receiveCart( {
			shipping_rates: [
				generateShippingPackage( {
					packageId: 0,
					shippingRates: offers,
				} ),
			],
		} );
		if ( addressIncomplete ) {
			dispatch( validationStore ).setValidationErrors( {
				shipping_city: { message: 'Enter a city', hidden: true },
			} );
		}
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
	dispatch( validationStore ).clearValidationErrors();
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
			screen.getByRole( 'radio', {
				name: ( name: string ) =>
					name.startsWith( 'backup shipping text' ),
			} )
		).toHaveTextContent( '$5.00' );
		expect(
			screen.queryByText( 'calculated with an address' )
		).not.toBeInTheDocument();
	} );

	// Hiding costs until an address is entered must not change what the shopper reads here.
	// With nothing deliverable there is no price to hide, only the prompt to enter an address.
	it( 'keeps the address prompt for pickup-only rates even with costs hidden and the address incomplete', () => {
		renderWith( {
			costRequiresAddress: true,
			addressIncomplete: true,
			offers: [ collectionRate ],
		} );

		expect(
			screen.getByRole( 'radio', {
				name: ( name: string ) =>
					name.startsWith( 'backup shipping text' ),
			} )
		).toHaveTextContent( 'calculated with an address' );
	} );
} );
