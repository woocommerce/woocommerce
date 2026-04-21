/* eslint-disable @typescript-eslint/no-explicit-any */
/**
 * External dependencies
 */
import { render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { fieldExtensions } from './field';

let lastDatePickerProps: any;

jest.mock( '../components/date-picker', () => ( {
	DatePicker: ( props: any ) => {
		lastDatePickerProps = props;
		return <div data-testid="date-picker" />;
	},
	parseDateTimeLocal: jest.requireActual( '../components/date-picker' )
		.parseDateTimeLocal,
} ) );

const Edit = fieldExtensions.Edit!;
const baseField = { id: 'date_on_sale_to', label: 'Sale end' } as any;

const makeProduct = ( overrides: Record< string, any > = {} ) => ( {
	date_on_sale_from: '',
	date_on_sale_to: '',
	...overrides,
} );

describe( 'date_on_sale_to field', () => {
	beforeEach( () => {
		lastDatePickerProps = undefined;
		jest.clearAllMocks();
	} );

	describe( 'Edit', () => {
		it( 'sets min to start date + 1 minute when start date exists', () => {
			render(
				<Edit
					data={
						makeProduct( {
							date_on_sale_from: '2025-03-15T10:00',
							date_on_sale_to: '2025-03-20T12:00',
						} ) as any
					}
					onChange={ jest.fn() }
					field={ baseField }
				/>
			);

			const min = lastDatePickerProps.min as Date;
			expect( min.getFullYear() ).toBe( 2025 );
			expect( min.getMonth() ).toBe( 2 ); // March
			expect( min.getDate() ).toBe( 15 );
			expect( min.getHours() ).toBe( 10 );
			expect( min.getMinutes() ).toBe( 1 );
		} );

		it( 'prevents end date from equalling start date', () => {
			render(
				<Edit
					data={
						makeProduct( {
							date_on_sale_from: '2025-03-15T10:00',
							date_on_sale_to: '2025-03-20T12:00',
						} ) as any
					}
					onChange={ jest.fn() }
					field={ baseField }
				/>
			);

			const min = lastDatePickerProps.min as Date;
			const start = new Date( 2025, 2, 15, 10, 0 );

			// min must be strictly after start
			expect( min.getTime() ).toBeGreaterThan( start.getTime() );
		} );

		it( 'sets min to today when no start date', () => {
			render(
				<Edit
					data={
						makeProduct( {
							date_on_sale_to: '2025-03-20T12:00',
						} ) as any
					}
					onChange={ jest.fn() }
					field={ baseField }
				/>
			);

			const min = lastDatePickerProps.min as Date;
			const today = new Date();
			today.setHours( 0, 0, 0, 0 );

			expect( min.getTime() ).toBe( today.getTime() );
		} );

		it( 'disables calendar dates before start date', () => {
			render(
				<Edit
					data={
						makeProduct( {
							date_on_sale_from: '2025-03-15T10:00',
							date_on_sale_to: '2025-03-20T12:00',
						} ) as any
					}
					onChange={ jest.fn() }
					field={ baseField }
				/>
			);

			const march14 = new Date( 2025, 2, 14 );
			const march15 = new Date( 2025, 2, 15 );
			const march16 = new Date( 2025, 2, 16 );

			expect( lastDatePickerProps.isDateDisabled( march14 ) ).toBe(
				true
			);
			expect( lastDatePickerProps.isDateDisabled( march15 ) ).toBe(
				false
			);
			expect( lastDatePickerProps.isDateDisabled( march16 ) ).toBe(
				false
			);
		} );

		it( 'disables calendar dates before today when no start date', () => {
			render(
				<Edit
					data={
						makeProduct( {
							date_on_sale_to: '2025-03-20T12:00',
						} ) as any
					}
					onChange={ jest.fn() }
					field={ baseField }
				/>
			);

			const yesterday = new Date();
			yesterday.setDate( yesterday.getDate() - 1 );

			const tomorrow = new Date();
			tomorrow.setDate( tomorrow.getDate() + 1 );

			expect( lastDatePickerProps.isDateDisabled( yesterday ) ).toBe(
				true
			);
			expect( lastDatePickerProps.isDateDisabled( tomorrow ) ).toBe(
				false
			);
		} );

		it( 'passes onChange directly without wrapper', () => {
			const onChange = jest.fn();

			render(
				<Edit
					data={
						makeProduct( {
							date_on_sale_to: '2025-03-20T12:00',
						} ) as any
					}
					onChange={ onChange }
					field={ baseField }
				/>
			);

			expect( lastDatePickerProps.onChange ).toBe( onChange );
		} );
	} );
} );
/* eslint-enable @typescript-eslint/no-explicit-any */
