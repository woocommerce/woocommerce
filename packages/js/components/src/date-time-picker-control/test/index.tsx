/**
 * External dependencies
 */
import { render, waitFor, fireEvent } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { createElement } from '@wordpress/element';
import { isRTL } from '@wordpress/i18n/build-types';
import moment from 'moment';

/**
 * Internal dependencies
 */
import { DateTimePickerControl } from '../';

describe( 'DateTimePickerControl', () => {
	it.skip( 'should render the expected DOM elements', () => {
		const { container } = render(
			<DateTimePickerControl
				label="This is the label"
				help="This is the help"
				placeholder="This is the placeholder"
			/>
		);

		const control = container.querySelector(
			'.woocommerce-date-time-picker-control'
		);
		expect( control ).toBeInTheDocument();

		const label = control?.querySelector(
			'.components-input-control__label'
		);
		expect( label ).toBeInTheDocument();

		const help = control?.querySelector(
			'.woocommerce-date-time-picker-control__help'
		);
		expect( help ).toBeInTheDocument();
	} );

	it( 'should add a class name if set', () => {
		const { container } = render(
			<DateTimePickerControl className="custom-class-name" />
		);

		const control = container.querySelector( '.custom-class-name' );
		expect( control ).toBeInTheDocument();
	} );

	it( 'should render a label if set', () => {
		const { getByText } = render(
			<DateTimePickerControl label="This is the label" />
		);

		expect( getByText( 'This is the label' ) ).toBeInTheDocument();
	} );

	it( 'should render a help message if set', () => {
		const { getByText } = render(
			<DateTimePickerControl help="This is the help" />
		);

		expect( getByText( 'This is the help' ) ).toBeInTheDocument();
	} );

	it( 'should render a placeholder if set', () => {
		const { container } = render(
			<DateTimePickerControl placeholder="This is the placeholder" />
		);

		const input = container.querySelector( 'input' );
		expect( input?.placeholder === 'This is the placeholder' );
	} );

	it( 'should disable the input if set', () => {
		const { container } = render(
			<DateTimePickerControl disabled={ true } />
		);

		const input = container.querySelector( 'input' );
		expect( input ).toBeDisabled();
	} );

	it( 'should use the default 24 hour date time format', () => {
		const dateTime = moment( '2022-09-15 02:30:40' );

		const { container } = render(
			<DateTimePickerControl currentDate={ dateTime.toISOString() } />
		);

		const input = container.querySelector( 'input' );
		expect( input?.value === '09/15/2022 02:30' );
	} );

	it( 'should use the default 12 hour date time format', () => {
		const dateTime = moment( '2022-09-15 02:30:40' );

		const { container } = render(
			<DateTimePickerControl
				currentDate={ dateTime.toISOString() }
				is12Hour={ true }
			/>
		);

		const input = container.querySelector( 'input' );
		expect( input?.value === '09/15/2022 02:30 AM' );
	} );

	it( 'should use the date time format if set', () => {
		const dateTime = moment( '2022-09-15 02:30:40' );

		const { container } = render(
			<DateTimePickerControl
				currentDate={ dateTime.toISOString() }
				dateTimeFormat="H:mm, MM-DD-YYYY"
			/>
		);

		const input = container.querySelector( 'input' );
		expect( input?.value === '02:30, 09-15-2022' );
	} );

	it( 'should show the date time picker popup when focused', async () => {
		const { container, queryByText } = render( <DateTimePickerControl /> );

		const input = container.querySelector( 'input' );

		userEvent.click( input! );

		await waitFor( () =>
			expect(
				container.querySelector( '.components-dropdown__content' )
			).toBeInTheDocument()
		);
	} );

	it( 'should hide the date time picker popup when no longer focused', async () => {
		const { container } = render( <DateTimePickerControl /> );

		const input = container.querySelector(
			'.woocommerce-date-time-picker-control input'
		);
		userEvent.click( input! );
		fireEvent.blur( input! );

		await waitFor( () =>
			expect(
				container.querySelector( '.components-dropdown__content' )
			).not.toBeInTheDocument()
		);
	} );

	it( 'should set the date time picker popup to 12 hour mode', async () => {
		const { container, queryByText } = render(
			<DateTimePickerControl is12Hour={ true } />
		);

		const input = container.querySelector( 'input' );

		userEvent.click( input! );

		await waitFor( () =>
			expect(
				container.querySelector(
					'.components-datetime__time-pm-button'
				)
			).toBeInTheDocument()
		);
	} );

	it( 'should call onBlur when losing focus', async () => {
		const onBlurHandler = jest.fn();

		const { container } = render(
			<div>
				<input />
				<DateTimePickerControl onBlur={ onBlurHandler } />
			</div>
		);

		userEvent.click(
			container.querySelector(
				'.woocommerce-date-time-picker-control input'
			)!
		);
		userEvent.click( container.querySelector( ':scope > input' )! );

		await waitFor( () =>
			expect( onBlurHandler ).toHaveBeenCalledTimes( 1 )
		);
	} );

	it( 'should call onChange when the input is changed', async () => {
		const originalDateTime = moment( '2022-09-15 02:30:40' );
		const dateTimeFormat = 'HH:mm, MM-DD-YYYY';
		const newDateTimeInputString = '02:04, 06-08-2010';
		const newDateTime = moment( newDateTimeInputString, dateTimeFormat );
		const onChangeHandler = jest.fn();

		const { container } = render(
			<div>
				<input />
				<DateTimePickerControl
					dateTimeFormat={ dateTimeFormat }
					currentDate={ originalDateTime.toISOString() }
					onChange={ onChangeHandler }
				/>
			</div>
		);

		const input = container.querySelector(
			'.woocommerce-date-time-picker-control input'
		);
		userEvent.click( input! );
		fireEvent.change( input!, {
			target: { value: newDateTimeInputString },
		} );

		await waitFor( () =>
			expect( onChangeHandler ).toHaveBeenLastCalledWith(
				newDateTime.toISOString()
			)
		);
	} );
} );
