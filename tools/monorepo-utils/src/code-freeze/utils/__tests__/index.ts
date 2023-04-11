/**
 * Internal dependencies
 */
import { verifyDay } from '../index';

describe( 'verifyDay', () => {
	it( 'should return a string', () => {
		expect( verifyDay() ).toBe( 'Today is a good day to code freeze!' );
	} );
} );
