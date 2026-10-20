/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { resetLocaleData, setLocaleData } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import HistoricalData from '../index';
import HistoricalDataLayout from '../layout';
import { formatParams } from '../utils';

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	withSelect: () => ( Component ) => ( props ) => (
		<Component
			{ ...props }
			selectedPeriod={ { label: 'all', date: '' } }
			skipChecked
		/>
	),
	withDispatch: () => ( Component ) => Component,
} ) );
jest.mock( '../layout', () => jest.fn( () => null ) );

describe( 'HistoricalData', () => {
	beforeEach( () => {
		jest.useFakeTimers().setSystemTime( new Date( '2026-10-13T12:00:00' ) );
	} );

	afterEach( () => {
		jest.useRealTimers();
		resetLocaleData();
		HistoricalDataLayout.mockClear();
	} );

	it.each( [
		[ 'untranslated', undefined, 'MM/DD/YYYY', '09/25/2025' ],
		[ 'day-first', 'DD/MM/YYYY', 'DD/MM/YYYY', '25/09/2025' ],
		[ 'dotted', 'DD.MM.YYYY', 'DD.MM.YYYY', '25.09.2025' ],
		[ 'hr', 'D/M/YYYY', 'DD/MM/YYYY', '25/09/2025' ],
		[ 'cs_CZ', 'MM/DD/RRRR', 'MM/DD/YYYY', '09/25/2025' ],
		[ 'fr_FR', 'JJ/MM/AAAA', 'MM/DD/YYYY', '09/25/2025' ],
		[ 'lowercase', 'dd/mm/aaaa', 'MM/DD/YYYY', '09/25/2025' ],
	] )(
		'imports from the typed "Beginning on" date for the %s translation',
		( _, translatedFormat, format, typedDate ) => {
			if ( translatedFormat ) {
				setLocaleData(
					{ 'MM/DD/YYYY': [ translatedFormat ] },
					'woocommerce'
				);
			}
			render( <HistoricalData /> );

			const { dateFormat } = HistoricalDataLayout.mock.calls[ 0 ][ 0 ];
			expect( dateFormat ).toBe( format );
			// 2025-09-25 is 383 days before the mocked 2026-10-13.
			expect(
				formatParams(
					dateFormat,
					{ label: 'custom', date: typedDate },
					false
				)
			).toEqual( { days: 383 } );
		}
	);
} );
