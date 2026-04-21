/* eslint-disable @typescript-eslint/no-explicit-any */
/**
 * External dependencies
 */
import { act, render } from '@testing-library/react';

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
	formatDateTimeLocal: jest.requireActual( '../components/date-picker' )
		.formatDateTimeLocal,
} ) );

const Edit = fieldExtensions.Edit!;
const baseField = { id: 'date_on_sale_from', label: 'Sale start' } as any;

const makeProduct = ( overrides: Record< string, any > = {} ) => ( {
	date_on_sale_from: '',
	date_on_sale_to: '',
	...overrides,
} );

describe( 'date_on_sale_from field', () => {
	beforeEach( () => {
		lastDatePickerProps = undefined;
		jest.clearAllMocks();
	} );

	describe( 'isVisible', () => {
		it( 'returns true when date_on_sale_from is set', () => {
			expect(
				fieldExtensions.isVisible!(
					makeProduct( {
						date_on_sale_from: '2025-03-01T00:00',
					} ) as any,
					{} as any
				)
			).toBe( true );
		} );

		it( 'returns true when date_on_sale_to is set', () => {
			expect(
				fieldExtensions.isVisible!(
					makeProduct( {
						date_on_sale_to: '2025-03-01T00:00',
					} ) as any,
					{} as any
				)
			).toBe( true );
		} );

		it( 'returns false when neither date is set', () => {
			expect(
				fieldExtensions.isVisible!( makeProduct() as any, {} as any )
			).toBe( false );
		} );
	} );

	describe( 'Edit', () => {
		it( 'passes isDateDisabled that rejects past dates', () => {
			render(
				<Edit
					data={ makeProduct() as any }
					onChange={ jest.fn() }
					field={ baseField }
				/>
			);

			const yesterday = new Date();
			yesterday.setDate( yesterday.getDate() - 1 );
			expect( lastDatePickerProps.isDateDisabled( yesterday ) ).toBe(
				true
			);

			const tomorrow = new Date();
			tomorrow.setDate( tomorrow.getDate() + 1 );
			expect( lastDatePickerProps.isDateDisabled( tomorrow ) ).toBe(
				false
			);
		} );

		it( 'passes min as today', () => {
			render(
				<Edit
					data={ makeProduct() as any }
					onChange={ jest.fn() }
					field={ baseField }
				/>
			);

			const min = lastDatePickerProps.min as Date;
			const today = new Date();
			today.setHours( 0, 0, 0, 0 );

			expect( min.getTime() ).toBe( today.getTime() );
		} );

		it( 'calls onChange with value when no end date conflict', () => {
			const onChange = jest.fn();

			render(
				<Edit
					data={
						makeProduct( {
							date_on_sale_from: '2025-03-01T00:00',
							date_on_sale_to: '2025-03-20T00:00',
						} ) as any
					}
					onChange={ onChange }
					field={ baseField }
				/>
			);

			act( () => {
				lastDatePickerProps.onChange( {
					date_on_sale_from: '2025-03-05T10:00',
				} );
			} );

			expect( onChange ).toHaveBeenCalledTimes( 1 );
			expect( onChange ).toHaveBeenCalledWith( {
				date_on_sale_from: '2025-03-05T10:00',
			} );
		} );

		it( 'auto-adjusts end date when start >= end in a single onChange call', () => {
			const onChange = jest.fn();

			render(
				<Edit
					data={
						makeProduct( {
							date_on_sale_from: '2025-03-01T00:00',
							date_on_sale_to: '2025-03-10T00:00',
						} ) as any
					}
					onChange={ onChange }
					field={ baseField }
				/>
			);

			act( () => {
				lastDatePickerProps.onChange( {
					date_on_sale_from: '2025-03-15T10:00',
				} );
			} );

			expect( onChange ).toHaveBeenCalledTimes( 1 );
			expect( onChange ).toHaveBeenCalledWith( {
				date_on_sale_from: '2025-03-15T10:00',
				date_on_sale_to: '2025-03-16T10:00',
			} );
		} );

		it( 'auto-adjusts end date when start equals end exactly', () => {
			const onChange = jest.fn();

			render(
				<Edit
					data={
						makeProduct( {
							date_on_sale_from: '2025-03-01T00:00',
							date_on_sale_to: '2025-03-10T12:00',
						} ) as any
					}
					onChange={ onChange }
					field={ baseField }
				/>
			);

			act( () => {
				lastDatePickerProps.onChange( {
					date_on_sale_from: '2025-03-10T12:00',
				} );
			} );

			expect( onChange ).toHaveBeenCalledTimes( 1 );
			expect( onChange ).toHaveBeenCalledWith( {
				date_on_sale_from: '2025-03-10T12:00',
				date_on_sale_to: '2025-03-11T12:00',
			} );
		} );

		it( 'does not adjust end date when end is not set', () => {
			const onChange = jest.fn();

			render(
				<Edit
					data={
						makeProduct( {
							date_on_sale_from: '',
							date_on_sale_to: '',
						} ) as any
					}
					onChange={ onChange }
					field={ baseField }
				/>
			);

			act( () => {
				lastDatePickerProps.onChange( {
					date_on_sale_from: '2025-03-15T10:00',
				} );
			} );

			expect( onChange ).toHaveBeenCalledTimes( 1 );
			expect( onChange ).toHaveBeenCalledWith( {
				date_on_sale_from: '2025-03-15T10:00',
			} );
		} );

		it( 'does not adjust end date when start is cleared', () => {
			const onChange = jest.fn();

			render(
				<Edit
					data={
						makeProduct( {
							date_on_sale_from: '2025-03-01T00:00',
							date_on_sale_to: '2025-03-10T00:00',
						} ) as any
					}
					onChange={ onChange }
					field={ baseField }
				/>
			);

			act( () => {
				lastDatePickerProps.onChange( {
					date_on_sale_from: null,
				} );
			} );

			expect( onChange ).toHaveBeenCalledTimes( 1 );
			expect( onChange ).toHaveBeenCalledWith( {
				date_on_sale_from: null,
			} );
		} );
	} );
} );
/* eslint-enable @typescript-eslint/no-explicit-any */
