/**
 * Internal dependencies
 */
import { getUrlParams, getTimeFrame } from '../index';

describe( 'getUrlParams', () => {
	let locationSearch = '?param1=text1&param2=text2';

	test( 'should return an object with sent params', () => {
		const { param1, param2 } = getUrlParams( locationSearch );
		expect( param1 ).toEqual( 'text1' );
		expect( param2 ).toEqual( 'text2' );
	} );

	test( 'should return an object with 2 keys/params', () => {
		const params = getUrlParams( locationSearch );
		expect( Object.keys( params ).length ).toEqual( 2 );
	} );

	test( 'should return an empty object', () => {
		locationSearch = '';
		const params = getUrlParams( locationSearch );
		expect( Object.keys( params ).length ).toEqual( 0 );
	} );

	test( 'should return an object with key "no_value" equal to "undefined"', () => {
		locationSearch = 'no_value';
		const { no_value: noValue } = getUrlParams( locationSearch );
		expect( noValue ).toBeUndefined();
	} );
} );

describe( 'getTimeFrame', () => {
	test.each( [
		{
			timeInMs: 1000,
			expected: '0-2s',
		},
		{
			timeInMs: 3000,
			expected: '2-5s',
		},
		{
			timeInMs: 100000,
			expected: '>60s',
		},
	] )(
		'should return time frames $expected when given $timeInMs',
		( { timeInMs, expected } ) => {
			expect( getTimeFrame( timeInMs ) ).toEqual( expected );
		}
	);
} );
