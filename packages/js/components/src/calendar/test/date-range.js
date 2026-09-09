/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { createElement } from '@wordpress/element';
import moment from 'moment';

/**
 * Internal dependencies
 */
import DateRange from '../date-range';

// The moments have to be built inside the test: moment.updateLocale() swaps the
// locale object, and existing moments (and their clones) keep the old one.
const renderDateRange = () =>
	render(
		<DateRange
			after={ moment( '2026-08-01' ) }
			before={ moment( '2026-08-20' ) }
			afterText="08/01/2026"
			beforeText="08/20/2026"
			focusedInput="startDate"
			onUpdate={ () => {} }
			shortDateFormat="MM/DD/YYYY"
		/>
	);

const getDayLabels = ( container ) =>
	Array.from(
		container.querySelectorAll( 'td.CalendarDay[aria-label]' )
	).map( ( day ) => day.getAttribute( 'aria-label' ) );

// WordPress seeds moment's long date formats with the site's PHP `date_format`
// option, which moment reads as its own tokens.
const seedLongDateFormat = ( format ) =>
	moment.updateLocale( moment.locale(), {
		longDateFormat: { LL: format },
	} );

// 'F j, Y' is the WordPress default, and moment prints its tokens literally.
// 'd/m/Y' is worse: moment reads `d` as the weekday index and `m` as minutes,
// so it renders a plausible but wrong date.
const phpDateFormats = [ 'F j, Y', 'd/m/Y' ];

describe( 'DateRange', () => {
	let originalLongDateFormat;

	beforeEach( () => {
		originalLongDateFormat = moment.localeData().longDateFormat( 'LL' );
	} );

	afterEach( () => {
		seedLongDateFormat( originalLongDateFormat );
	} );

	it.each( phpDateFormats )(
		'labels calendar days with a real date when `LL` is "%s"',
		( phpDateFormat ) => {
			seedLongDateFormat( phpDateFormat );

			const { container } = renderDateRange();
			const labels = getDayLabels( container );

			expect( labels ).toContain( 'Selected. Saturday, August 1, 2026' );
			expect( labels ).toContain(
				'Select Wednesday, August 26, 2026 as a start date.'
			);
		}
	);

	it.each( phpDateFormats )(
		'labels every day with a real date when `LL` is "%s"',
		( phpDateFormat ) => {
			seedLongDateFormat( phpDateFormat );

			const { container } = renderDateRange();
			const labels = getDayLabels( container );

			expect( labels.length ).toBeGreaterThan( 0 );
			labels.forEach( ( label ) => {
				expect( label ).toMatch(
					/[A-Z][a-z]+day, [A-Z][a-z]+ \d{1,2}, \d{4}/
				);
			} );
		}
	);
} );
