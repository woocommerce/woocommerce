/**
 * External dependencies
 */
import { act, render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { useShippingData } from '@woocommerce/base-context/hooks';

/**
 * Internal dependencies
 */
import { ShippingRatesControlPackage } from '../index';
import {
	generateShippingPackage,
	generateShippingRate,
} from '../../../../../mocks/shipping-package';

jest.mock( '@woocommerce/base-context/hooks' );

const testPackageData = generateShippingPackage( {
	packageId: 0,
	shippingRates: [
		generateShippingRate( {
			rateId: 'flat_rate:1',
			name: 'Flat rate',
			price: '1000',
			instanceID: 1,
		} ),
		generateShippingRate( {
			rateId: 'flat_rate:2',
			name: 'Flat rate (premium)',
			price: '1500',
			instanceID: 5,
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

	render(
		<ShippingRatesControlPackage
			packageData={ testPackageData }
			packageId={ testPackageData.package_id }
			noResultsMessage={
				<span>No shipping rates available at the moment</span>
			}
		/>
	);

	const firstRate = await screen.findByRole( 'radio', {
		name: 'Flat rate $10.00',
	} );

	expect( firstRate ).toBeInTheDocument();
	// even though it's not selected we mark first one as checked by default
	expect( firstRate ).toBeChecked();

	expect(
		screen.getByRole( 'radio', { name: 'Flat rate (premium) $15.00' } )
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

	render(
		<ShippingRatesControlPackage
			packageData={ testPackageData }
			packageId={ testPackageData.package_id }
			noResultsMessage={
				<span>No shipping rates available at the moment</span>
			}
		/>
	);

	const firstRate = await screen.findByRole( 'radio', {
		name: 'Flat rate $10.00',
	} );
	const secondRate = screen.getByRole( 'radio', {
		name: 'Flat rate (premium) $15.00',
	} );

	expect( firstRate ).toBeInTheDocument();
	expect( firstRate ).toBeChecked();

	await act( async () => {
		await userEvent.click( secondRate );
	} );

	expect( secondRate ).toBeChecked();
	expect( selectShippingRate ).toHaveBeenLastCalledWith( 'flat_rate:2', 0 );
} );

test( 'upstream rate selection updates are properly reflected in local state', async () => {
	const packageData = generateShippingPackage( {
		packageId: 0,
		shippingRates: [
			generateShippingRate( {
				rateId: 'flat_rate:1',
				name: 'Flat rate',
				price: '1000',
				instanceID: 1,
				selected: false,
			} ),
			generateShippingRate( {
				rateId: 'flat_rate:2',
				name: 'Flat rate (premium)',
				price: '1500',
				instanceID: 5,
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

	const { rerender } = render(
		<ShippingRatesControlPackage
			packageData={ packageData }
			packageId={ packageData.package_id }
			noResultsMessage={
				<span>No shipping rates available at the moment</span>
			}
		/>
	);

	const firstRate = await screen.findByRole( 'radio', {
		name: 'Flat rate $10.00',
	} );
	const secondRate = screen.getByRole( 'radio', {
		name: 'Flat rate (premium) $15.00',
	} );

	expect( firstRate ).toBeInTheDocument();
	expect( secondRate ).toBeInTheDocument();
	expect( firstRate ).not.toBeChecked();
	expect( secondRate ).toBeChecked();

	const packageDataWithFlippedSelection = generateShippingPackage( {
		packageId: 0,
		shippingRates: [
			generateShippingRate( {
				rateId: 'flat_rate:1',
				name: 'Flat rate',
				price: '1000',
				instanceID: 1,
				selected: true,
			} ),
			generateShippingRate( {
				rateId: 'flat_rate:2',
				name: 'Flat rate (premium)',
				price: '1500',
				instanceID: 5,
				selected: false,
			} ),
		],
	} );

	( useShippingData as jest.Mock ).mockImplementation( () => {
		return {
			selectShippingRate: jest.fn(),
			isSelectingRate: false,
			shippingRates: packageDataWithFlippedSelection,
		};
	} );

	rerender(
		<ShippingRatesControlPackage
			packageData={ packageDataWithFlippedSelection }
			packageId={ packageDataWithFlippedSelection.package_id }
			noResultsMessage={
				<span>No shipping rates available at the moment</span>
			}
		/>
	);

	expect( firstRate ).toBeInTheDocument();
	expect( secondRate ).toBeInTheDocument();
	expect( firstRate ).toBeChecked();
	expect( secondRate ).not.toBeChecked();
} );
