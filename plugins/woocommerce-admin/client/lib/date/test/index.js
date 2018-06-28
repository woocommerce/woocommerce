/** @format */
/**
 * External dependencies
 */
import moment from 'moment';

/**
 * Internal dependencies
 */
import { toMoment } from 'lib/date';

describe( 'toMoment', () => {
	it( 'should pass through a valid Moment object as an argument', () => {
		const now = moment();
		const myMoment = toMoment( 'YYYY', now );
		expect( myMoment ).toEqual( now );
	} );

	it( 'should handle isoFormat dates', () => {
		const myMoment = toMoment( 'YYYY', '2018-04-15' );
		expect( moment.isMoment( myMoment ) ).toBe( true );
		expect( myMoment.isValid() ).toBe( true );
	} );

	it( 'should handle local formats', () => {
		const longDate = toMoment( 'MMMM D, YYYY', 'April 15, 2018' );
		expect( moment.isMoment( longDate ) ).toBe( true );
		expect( longDate.isValid() ).toBe( true );
		expect( longDate.date() ).toBe( 15 );
		expect( longDate.month() ).toBe( 3 );
		expect( longDate.year() ).toBe( 2018 );

		const shortDate = toMoment( 'DD/MM/YYYY', '15/04/2018' );
		expect( moment.isMoment( shortDate ) ).toBe( true );
		expect( shortDate.isValid() ).toBe( true );
		expect( shortDate.date() ).toBe( 15 );
		expect( shortDate.month() ).toBe( 3 );
		expect( shortDate.year() ).toBe( 2018 );
	} );

	it( 'should throw on an invalid argument', () => {
		const fn = () => toMoment( '', 77 );
		expect( fn ).toThrow();
	} );
} );
