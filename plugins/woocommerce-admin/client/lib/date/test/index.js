/** @format */
/**
 * External dependencies
 */
import moment from 'moment';
import { getSettings, setSettings } from '@wordpress/date';

/**
 * Internal dependencies
 */
import { toMoment } from 'lib/date';

const initialSettings = getSettings();

beforeEach( () => {
	setSettings( initialSettings );
} );

describe( 'toMoment', () => {
	it( 'should pass through a valid Moment object as an argument', () => {
		const now = moment();
		const myMoment = toMoment( now );
		expect( myMoment ).toEqual( now );
	} );

	it( 'should handle isoFormat dates', () => {
		const myMoment = toMoment( '2018-04-15' );
		expect( moment.isMoment( myMoment ) ).toBe( true );
		expect( myMoment.isValid() ).toBe( true );
	} );

	it( 'should handle default local formats', () => {
		const myMoment = toMoment( 'April 15, 2018' );
		expect( moment.isMoment( myMoment ) ).toBe( true );
		expect( myMoment.isValid() ).toBe( true );
		expect( myMoment.date() ).toBe( 15 );
		expect( myMoment.month() ).toBe( 3 );
		expect( myMoment.year() ).toBe( 2018 );
	} );

	it( 'should handle local formats', () => {
		const settings = getSettings();
		settings.formats.date = 'd/m/Y';
		setSettings( settings );
		const myMoment = toMoment( '15/04/2018' );
		expect( moment.isMoment( myMoment ) ).toBe( true );
		expect( myMoment.isValid() ).toBe( true );
		expect( myMoment.date() ).toBe( 15 );
		expect( myMoment.month() ).toBe( 3 );
		expect( myMoment.year() ).toBe( 2018 );
	} );

	it( 'should throw on an invalid argument', () => {
		const fn = () => toMoment( 77 );
		expect( fn ).toThrow();
	} );
} );
