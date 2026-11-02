/**
 * External dependencies
 */
import { utcParse as d3UTCParse } from 'd3-time-format';

/**
 * Internal dependencies
 */
import dummyOrders from './fixtures/dummy-orders';
import orderedKeys from './fixtures/dummy-ordered-keys';
import { getDateLabel, getOrderedKeys, isDataEmpty } from '../index';

const parseDate = d3UTCParse( '%Y-%m-%dT%H:%M:%S' );
const testOrderedKeys = getOrderedKeys( dummyOrders );

describe( 'getDateLabel', () => {
	const formatter = ( date ) =>
		`${ date.getFullYear() }-${ date.getMonth() + 1 }-${ date.getDate() }`;

	it( 'formats a single date given as a string', () => {
		expect( getDateLabel( formatter, '2020-02-28 00:00:00' ) ).toEqual(
			'2020-2-28'
		);
	} );

	it( 'formats a single date given as a Date', () => {
		expect( getDateLabel( formatter, new Date( 2020, 1, 28 ) ) ).toEqual(
			'2020-2-28'
		);
	} );

	it( 'joins both ends when the point covers a date range', () => {
		expect(
			getDateLabel(
				formatter,
				'2020-02-28 00:00:00',
				'2020-02-29 00:00:00'
			)
		).toEqual( '2020-2-28 - 2020-2-29' );
	} );
} );

describe( 'parseDate', () => {
	it( 'correctly parse date in the expected format', () => {
		const testDate = parseDate( '2018-06-30T00:00:00' );
		const expectedDate = new Date( Date.UTC( 2018, 5, 30 ) );
		expect( testDate.getTime() ).toEqual( expectedDate.getTime() );
	} );
} );

describe( 'getOrderedKeys', () => {
	it( 'returns an array of keys order by value from largest to smallest', () => {
		expect( testOrderedKeys ).toEqual( orderedKeys );
	} );
} );

describe( 'isDataEmpty', () => {
	it( 'should return true when all data values are 0 and no baseValue is provided', () => {
		const data = [
			{
				lorem: {
					value: 0,
				},
				ipsum: {
					value: 0,
				},
			},
		];
		expect( isDataEmpty( data ) ).toBeTruthy();
	} );

	it( 'should return true when all data values match the base value', () => {
		const data = [
			{
				lorem: {
					value: 100,
				},
				ipsum: {
					value: 100,
				},
			},
		];
		expect( isDataEmpty( data, 100 ) ).toBeTruthy();
	} );

	it( "should return false if at least one data values doesn't match the base value", () => {
		const data = [
			{
				lorem: {
					value: 100,
				},
				ipsum: {
					value: 0,
				},
			},
		];
		expect( isDataEmpty( data, 100 ) ).toBeFalsy();
	} );

	it( 'should return true when all data values match the base value or are null/undefined', () => {
		const data = [
			{
				lorem: {
					value: 100,
				},
				ipsum: {
					value: null,
				},
				dolor: {},
			},
		];
		expect( isDataEmpty( data, 100 ) ).toBeTruthy();
	} );
} );
