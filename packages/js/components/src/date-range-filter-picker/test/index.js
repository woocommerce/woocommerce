/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { createElement } from '@wordpress/element';
import { resetLocaleData, setLocaleData } from '@wordpress/i18n';
import moment from 'moment';

/**
 * Internal dependencies
 */
import DateRangeFilterPicker from '../index';

const renderPicker = ( onRangeSelect ) =>
	render(
		<DateRangeFilterPicker
			onRangeSelect={ onRangeSelect }
			isoDateFormat="YYYY-MM-DD"
			dateQuery={ {
				period: 'custom',
				compare: 'previous_year',
				after: moment( '2024-09-25' ),
				before: moment( '2024-10-05' ),
				primaryDate: { label: 'Custom', range: 'Sep 25 - Oct 5, 2024' },
				secondaryDate: {
					label: 'Previous year',
					range: 'Sep 25 - Oct 5, 2023',
				},
			} }
		/>
	);

const typeDate = async ( label, value ) => {
	const input = screen.getByLabelText( label );
	await userEvent.clear( input );
	await userEvent.type( input, value );
};

describe( 'DateRangeFilterPicker', () => {
	afterEach( () => {
		resetLocaleData();
	} );

	it.each( [
		[ 'untranslated', undefined, '09/25/2024', '10/04/2024' ],
		[ 'day-first', 'DD/MM/YYYY', '25/09/2024', '04/10/2024' ],
		[ 'dotted day-first', 'DD.MM.YYYY', '25.09.2024', '04.10.2024' ],
	] )(
		'shows and parses typed dates in the %s format',
		async ( _, translatedFormat, afterText, typedBefore ) => {
			if ( translatedFormat ) {
				setLocaleData(
					{ 'MM/DD/YYYY': [ translatedFormat ] },
					'woocommerce'
				);
			}
			const onRangeSelect = jest.fn();
			renderPicker( onRangeSelect );

			await userEvent.click( screen.getByRole( 'button' ) );

			const startInput = screen.getByLabelText( 'Start Date' );
			expect( startInput ).toHaveValue( afterText );
			expect( startInput ).toHaveAttribute(
				'placeholder',
				( translatedFormat || 'MM/DD/YYYY' ).toLowerCase()
			);

			await typeDate( 'End Date', typedBefore );
			expect( screen.queryByText( 'Invalid date' ) ).toBeNull();

			await userEvent.click(
				screen.getByRole( 'button', { name: 'Update' } )
			);
			expect( onRangeSelect ).toHaveBeenCalledWith( {
				period: 'custom',
				compare: 'previous_year',
				after: '2024-09-25',
				before: '2024-10-04',
			} );
		}
	);
} );
