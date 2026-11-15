/**
 * @jest-environment jsdom
 */
/**
 * External dependencies
 */
import { fireEvent, render, screen } from '@testing-library/react';
import { createElement } from '@wordpress/element';
import { resetLocaleData, setLocaleData } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import DateFilter from '../date-filter';
import type { FilterConfig } from '../types';

const config: FilterConfig = {
	labels: {
		add: 'Date',
		remove: 'Remove date filter',
		rule: 'Select a date filter match',
		title: '<title>Date</title> <rule/> <filter/>',
		filter: 'Select a transaction date',
	},
	rules: [
		{ value: 'before', label: 'Before' },
		{ value: 'after', label: 'After' },
	],
	input: { component: 'Date' },
};

describe( 'DateFilter', () => {
	afterEach( () => {
		resetLocaleData();
	} );

	it.each( [
		[ 'untranslated', undefined, 'MM/DD/YYYY', '09/25/2024', '10/04/2024' ],
		[ 'day-first', 'DD/MM/YYYY', 'DD/MM/YYYY', '25/09/2024', '04/10/2024' ],
		[ 'dotted', 'DD.MM.YYYY', 'DD.MM.YYYY', '25.09.2024', '04.10.2024' ],
		[ 'hr', 'D/M/YYYY', 'DD/MM/YYYY', '25/09/2024', '04/10/2024' ],
		[ 'fr_FR', 'JJ/MM/AAAA', 'MM/DD/YYYY', '09/25/2024', '10/04/2024' ],
		[ 'cs_CZ', 'MM/DD/RRRR', 'MM/DD/YYYY', '09/25/2024', '10/04/2024' ],
		[ 'lowercase', 'dd/mm/aaaa', 'MM/DD/YYYY', '09/25/2024', '10/04/2024' ],
	] )(
		'shows and parses typed dates for the %s translation',
		( _, translatedFormat, format, shownText, typedText ) => {
			if ( translatedFormat ) {
				setLocaleData(
					{ 'MM/DD/YYYY': [ translatedFormat ] },
					'woocommerce'
				);
			}
			const onFilterChange = jest.fn();
			render(
				<DateFilter
					config={ config }
					filter={ {
						key: 'date',
						rule: 'before',
						value: '2024-09-25',
					} }
					onFilterChange={ onFilterChange }
				/>
			);

			const input = screen.getByRole( 'textbox', {
				name: 'Choose a date',
			} );
			expect( input ).toHaveValue( shownText );
			expect( input ).toHaveAttribute(
				'placeholder',
				format.toLowerCase()
			);

			fireEvent.change( input, { target: { value: typedText } } );
			expect( screen.queryByText( 'Invalid date' ) ).toBeNull();
			expect( onFilterChange ).toHaveBeenCalledWith( {
				property: 'value',
				value: '2024-10-04',
			} );
		}
	);
} );
