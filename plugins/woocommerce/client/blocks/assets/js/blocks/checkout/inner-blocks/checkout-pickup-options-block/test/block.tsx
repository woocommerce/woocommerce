/**
 * External dependencies
 */
import { act, render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { useShippingData, useStoreCart } from '@woocommerce/base-context/hooks';

/**
 * Internal dependencies
 */
import CheckoutPickupOptionsBlock from '../block';
import {
	generateShippingPackage,
	generateShippingRate,
} from '../../../../../mocks/shipping-package';

jest.mock( '@woocommerce/base-context/hooks' );

// getting rid of the slot fill
jest.mock( '@woocommerce/blocks-checkout', () => {
	const PassthroughComponent = ( {
		children,
	}: {
		children: React.ReactNode;
	} ) => <>{ children }</>;

	PassthroughComponent.Slot = PassthroughComponent;

	return {
		ExperimentalOrderLocalPickupPackages: PassthroughComponent,
	};
} );

// Setting needed to treat shipping rate as a local pickup.
// Can't rely on setting it through allSettings.collectableMethodIds = [...]
// as getSettings is used when the module is initialized (so before attempts
// to overwrite setting in beforeEach/All)
jest.mock( '@woocommerce/settings', () => {
	const actualModule = jest.requireActual( '@woocommerce/settings' );
	return {
		...actualModule,
		getSetting: ( name: string, ...rest: unknown[] ) => {
			if ( name === 'collectableMethodIds' ) {
				return [ 'pickup_nyc', 'pickup_la' ];
			}

			return actualModule.getSetting( name, ...rest );
		},
	};
} );

( useStoreCart as jest.Mock ).mockImplementation( () =>
	jest.requireActual( '@woocommerce/base-context/hooks' ).useStoreCart()
);

const testPackageData = generateShippingPackage( {
	packageId: 0,
	shippingRates: [
		generateShippingRate( {
			rateId: 'pickup_location:1',
			name: 'Pickup New York City',
			methodID: 'pickup_nyc',
			price: '0',
			instanceID: 0,
		} ),
		generateShippingRate( {
			rateId: 'pickup_location:2',
			name: 'Pickup Los Angeles',
			methodID: 'pickup_la',
			price: '0',
			instanceID: 1,
		} ),
	],
} );

test( 'renders available shipping rates', async () => {
	( useShippingData as jest.Mock ).mockImplementation( () => {
		return {
			selectShippingRate: jest.fn(),
			isSelectingRate: false,
			shippingRates: [ testPackageData ],
		};
	} );

	render( <CheckoutPickupOptionsBlock /> );

	const firstRate = await screen.findByRole( 'radio', {
		name: 'Pickup New York City free',
	} );

	expect( firstRate ).toBeInTheDocument();
	// even though it's not selected we mark first one as checked by default
	expect( firstRate ).toBeChecked();

	expect(
		screen.getByRole( 'radio', { name: 'Pickup Los Angeles free' } )
	).toBeInTheDocument();
} );

test( 'changes rate selection locally and informs API about it', async () => {
	const selectShippingRate = jest.fn();

	( useShippingData as jest.Mock ).mockImplementation( () => {
		return {
			selectShippingRate,
			isSelectingRate: false,
			shippingRates: [ testPackageData ],
		};
	} );

	render( <CheckoutPickupOptionsBlock /> );

	const firstRate = await screen.findByRole( 'radio', {
		name: 'Pickup New York City free',
	} );
	const secondRate = screen.getByRole( 'radio', {
		name: 'Pickup Los Angeles free',
	} );

	expect( firstRate ).toBeInTheDocument();
	expect( firstRate ).toBeChecked();

	await act( async () => {
		await userEvent.click( secondRate );
	} );

	expect( secondRate ).toBeChecked();
	expect( selectShippingRate ).toHaveBeenLastCalledWith(
		'pickup_location:2'
	);
} );

test( 'upstream rate selection updates are properly reflected in local state', async () => {
	const packageData = generateShippingPackage( {
		packageId: 0,
		shippingRates: [
			generateShippingRate( {
				rateId: 'pickup_location:1',
				name: 'Pickup New York City',
				methodID: 'pickup_nyc',
				price: '0',
				instanceID: 0,
				selected: false,
			} ),
			generateShippingRate( {
				rateId: 'pickup_location:2',
				name: 'Pickup Los Angeles',
				methodID: 'pickup_la',
				price: '0',
				instanceID: 1,
				selected: true,
			} ),
		],
	} );

	( useShippingData as jest.Mock ).mockImplementation( () => {
		return {
			selectShippingRate: jest.fn(),
			isSelectingRate: false,
			shippingRates: [ packageData ],
		};
	} );

	const { rerender } = render( <CheckoutPickupOptionsBlock /> );

	const firstRate = await screen.findByRole( 'radio', {
		name: 'Pickup New York City free',
	} );
	const secondRate = screen.getByRole( 'radio', {
		name: 'Pickup Los Angeles free',
	} );

	expect( firstRate ).toBeInTheDocument();
	expect( secondRate ).toBeInTheDocument();
	expect( firstRate ).not.toBeChecked();
	expect( secondRate ).toBeChecked();

	const packageDataWithFlippedSelection = generateShippingPackage( {
		packageId: 0,
		shippingRates: [
			generateShippingRate( {
				rateId: 'pickup_location:1',
				name: 'Pickup New York City',
				methodID: 'pickup_nyc',
				price: '0',
				instanceID: 0,
				selected: true,
			} ),
			generateShippingRate( {
				rateId: 'pickup_location:2',
				name: 'Pickup Los Angeles',
				methodID: 'pickup_la',
				price: '0',
				instanceID: 1,
				selected: false,
			} ),
		],
	} );

	( useShippingData as jest.Mock ).mockImplementation( () => {
		return {
			selectShippingRate: jest.fn(),
			isSelectingRate: false,
			shippingRates: [ packageDataWithFlippedSelection ],
		};
	} );

	rerender( <CheckoutPickupOptionsBlock /> );

	expect( firstRate ).toBeInTheDocument();
	expect( secondRate ).toBeInTheDocument();
	expect( firstRate ).toBeChecked();
	expect( secondRate ).not.toBeChecked();
} );

test( 'description is not shown if rate is not selected', async () => {
	const packageData = generateShippingPackage( {
		packageId: 0,
		shippingRates: [
			generateShippingRate( {
				rateId: 'pickup_location:1',
				name: 'Pickup New York City',
				methodID: 'pickup_nyc',
				price: '0',
				instanceID: 0,
				selected: false,
				meta_data: [
					{ key: 'pickup_details', value: 'Store 1 details.' },
				],
			} ),
			generateShippingRate( {
				rateId: 'pickup_location:2',
				name: 'Pickup Los Angeles',
				methodID: 'pickup_la',
				price: '0',
				instanceID: 1,
				selected: true,
				meta_data: [
					{ key: 'pickup_details', value: 'Store 2 details.' },
				],
			} ),
		],
	} );
	( useShippingData as jest.Mock ).mockImplementation( () => {
		return {
			selectShippingRate: jest.fn(),
			isSelectingRate: false,
			shippingRates: [ packageData ],
		};
	} );
	render( <CheckoutPickupOptionsBlock /> );
	expect( screen.queryByText( 'Store 1 details.' ) ).not.toBeInTheDocument();
	expect( screen.getByText( 'Store 2 details.' ) ).toBeInTheDocument();
} );
