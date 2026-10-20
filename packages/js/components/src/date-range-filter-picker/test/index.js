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
		[ 'untranslated', undefined, 'MM/DD/YYYY', '09/25/2024', '10/04/2024' ],
		[ 'day-first', 'DD/MM/YYYY', 'DD/MM/YYYY', '25/09/2024', '04/10/2024' ],
		[ 'dotted', 'DD.MM.YYYY', 'DD.MM.YYYY', '25.09.2024', '04.10.2024' ],
		// Croatian uses single tokens, which strict parsing rejects padded input for.
		[ 'hr', 'D/M/YYYY', 'DD/MM/YYYY', '25/09/2024', '04/10/2024' ],
		// Translations from real language packs that aren't Moment.js formats.
		// The French one is why #61028 stopped parsing with the translation.
		[ 'fr_FR', 'JJ/MM/AAAA', 'MM/DD/YYYY', '09/25/2024', '10/04/2024' ],
		[ 'cs_CZ', 'MM/DD/RRRR', 'MM/DD/YYYY', '09/25/2024', '10/04/2024' ],
		[ 'et', 'KK/PP/AAAA', 'MM/DD/YYYY', '09/25/2024', '10/04/2024' ],
		[ 'lowercase', 'dd/mm/aaaa', 'MM/DD/YYYY', '09/25/2024', '10/04/2024' ],
	] )(
		'shows and parses typed dates for the %s translation',
		async ( _, translatedFormat, format, afterText, typedBefore ) => {
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
				format.toLowerCase()
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
