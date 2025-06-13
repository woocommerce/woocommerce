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
			renderPickupLocation={ ( location ) => {
				return {
					value: `${ location.rate_id }`,
					onChange: jest.fn(),
					label: `${ location.name }`,
					description: `${ location.description }`,
				};
			} }
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
} );
