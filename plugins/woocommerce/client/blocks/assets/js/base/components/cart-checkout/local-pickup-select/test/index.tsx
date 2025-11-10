/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import LocalPickupSelect from '..';
import { generateShippingRate } from '../../../../../mocks/shipping-package';

describe( 'LocalPickupSelect', () => {
	const renderPickupLocationMock = jest
		.fn()
		.mockImplementation(
			( location, pickupLocationsCount, isSelectedInClient ) => {
				return {
					value: `${ location.rate_id }`,
					onChange: jest.fn(),
					label: `${ location.name }`,
					description: `${ location.description }`,
				};
			}
		);
	const TestComponent = ( {
		onChange,
	}: {
		onChange?: ( value: string ) => void;
	} ) => (
		<LocalPickupSelect
			title="Package 1"
			onChange={ onChange ?? jest.fn() }
			selectedOption=""
			pickupLocations={ [
				generateShippingRate( {
					rateId: '1',
					name: 'Store 1',
					instanceID: 1,
					price: '0',
				} ),
				generateShippingRate( {
					rateId: '2',
					name: 'Store 2',
					instanceID: 1,
					price: '0',
				} ),
			] }
			packageCount={ 1 }
			renderPickupLocation={ renderPickupLocationMock }
		/>
	);
	it( 'Does not render the title if only one package is present on the page', () => {
		render( <TestComponent /> );
		expect( screen.queryByText( 'Package 1' ) ).not.toBeInTheDocument();
	} );
	it( 'Does render the title if more than one package is present on the page', () => {
		const { rerender } = render(
			<div className="wc-block-components-local-pickup-select">
				<div className="wc-block-components-radio-control"></div>
			</div>
		);
		// Render twice so our component can check the DOM correctly.
		rerender(
			<>
				<div className="wc-block-components-local-pickup-select">
					<div className="wc-block-components-radio-control"></div>
				</div>
				<TestComponent />
			</>
		);
		rerender(
			<>
				<div className="wc-block-components-local-pickup-select">
					<div className="wc-block-components-radio-control"></div>
				</div>
				<TestComponent />
			</>
		);

		expect( screen.getByText( 'Package 1' ) ).toBeInTheDocument();
	} );
	it( 'Calls the correct functions when changing selected option', async () => {
		const user = userEvent.setup();
		const onChange = jest.fn();
		render( <TestComponent onChange={ onChange } /> );

		await user.click( screen.getByText( 'Store 2' ) );
		expect( onChange ).toHaveBeenLastCalledWith( '2' );

		await user.click( screen.getByText( 'Store 1' ) );
		expect( onChange ).toHaveBeenLastCalledWith( '1' );
	} );
	it( 'Calls renderPickupLocation with correct parameters', () => {
		renderPickupLocationMock.mockClear();
		render(
			<LocalPickupSelect
				title="Package 1"
				onChange={ jest.fn() }
				selectedOption="2"
				pickupLocations={ [
					generateShippingRate( {
						rateId: '1',
						name: 'Store 1',
						instanceID: 1,
						price: '0',
					} ),
					generateShippingRate( {
						rateId: '2',
						name: 'Store 2',
						instanceID: 1,
						price: '0',
					} ),
				] }
				packageCount={ 1 }
				renderPickupLocation={ renderPickupLocationMock }
			/>
		);

		// Should be called once for each pickup location
		expect( renderPickupLocationMock ).toHaveBeenCalledTimes( 2 );

		// First location: not selected
		expect( renderPickupLocationMock ).toHaveBeenNthCalledWith(
			1,
			expect.objectContaining( {
				rate_id: '1',
				name: 'Store 1',
			} ),
			1, // packageCount
			false // isSelectedInClient (location.rate_id '1' !== selectedOption '2')
		);

		// Second location: selected
		expect( renderPickupLocationMock ).toHaveBeenNthCalledWith(
			2,
			expect.objectContaining( {
				rate_id: '2',
				name: 'Store 2',
			} ),
			1, // packageCount
			true // isSelectedInClient (location.rate_id '2' === selectedOption '2')
		);
	} );
	it( 'Updates isSelectedInClient parameter when selection changes', () => {
		renderPickupLocationMock.mockClear();
		const pickupLocations = [
			generateShippingRate( {
				rateId: '1',
				name: 'Store 1',
				instanceID: 1,
				price: '0',
			} ),
			generateShippingRate( {
				rateId: '2',
				name: 'Store 2',
				instanceID: 1,
				price: '0',
			} ),
		];

		const { rerender } = render(
			<LocalPickupSelect
				title="Package 1"
				onChange={ jest.fn() }
				selectedOption="1"
				pickupLocations={ pickupLocations }
				packageCount={ 1 }
				renderPickupLocation={ renderPickupLocationMock }
			/>
		);

		// Initial render: Store 1 selected
		expect( renderPickupLocationMock ).toHaveBeenCalledTimes( 2 );
		expect( renderPickupLocationMock ).toHaveBeenNthCalledWith(
			1,
			expect.objectContaining( { rate_id: '1' } ),
			1,
			true // Store 1 is selected
		);
		expect( renderPickupLocationMock ).toHaveBeenNthCalledWith(
			2,
			expect.objectContaining( { rate_id: '2' } ),
			1,
			false // Store 2 is not selected
		);

		// Clear mock and rerender with different selection
		renderPickupLocationMock.mockClear();
		rerender(
			<LocalPickupSelect
				title="Package 1"
				onChange={ jest.fn() }
				selectedOption="2"
				pickupLocations={ pickupLocations }
				packageCount={ 1 }
				renderPickupLocation={ renderPickupLocationMock }
			/>
		);

		// After rerender: Store 2 selected
		expect( renderPickupLocationMock ).toHaveBeenCalledTimes( 2 );
		expect( renderPickupLocationMock ).toHaveBeenNthCalledWith(
			1,
			expect.objectContaining( { rate_id: '1' } ),
			1,
			false // Store 1 is not selected
		);
		expect( renderPickupLocationMock ).toHaveBeenNthCalledWith(
			2,
			expect.objectContaining( { rate_id: '2' } ),
			1,
			true // Store 2 is selected
		);
	} );
} );
