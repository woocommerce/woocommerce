/**
 * Internal dependencies
 */
import { isTodayCodeFreezeDay } from '../index';

describe( 'isTodayCodeFreezeDay', () => {
	it( 'should return false when given a day not 22 days before release', () => {
		const JUNE_5_2023 = '2023-06-05T00:00:00.000Z';
		const JUNE_12_2023 = '2023-06-12T00:00:00.000Z';
		const JUNE_26_2023 = '2023-06-26T00:00:00.000Z';
		const AUG_10_2023 = '2023-08-10T00:00:00.000Z';
		const AUG_17_2023 = '2023-08-17T00:00:00.000Z';
		const AUG_24_2023 = '2023-08-24T00:00:00.000Z';

		expect( isTodayCodeFreezeDay( JUNE_5_2023 ) ).toBeFalsy();
		expect( isTodayCodeFreezeDay( JUNE_12_2023 ) ).toBeFalsy();
		expect( isTodayCodeFreezeDay( JUNE_26_2023 ) ).toBeFalsy();
		expect( isTodayCodeFreezeDay( AUG_10_2023 ) ).toBeFalsy();
		expect( isTodayCodeFreezeDay( AUG_17_2023 ) ).toBeFalsy();
		expect( isTodayCodeFreezeDay( AUG_24_2023 ) ).toBeFalsy();
	} );

	it( 'should return true when given a day 22 days before release', () => {
		const JUNE_19_2023 = '2023-06-19T00:00:00.000Z';
		const JULY_17_2023 = '2023-07-17T00:00:00.000Z';
		const AUGUST_21_2023 = '2023-08-21T00:00:00.000Z';

		expect( isTodayCodeFreezeDay( JUNE_19_2023 ) ).toBeTruthy();
		expect( isTodayCodeFreezeDay( JULY_17_2023 ) ).toBeTruthy();
		expect( isTodayCodeFreezeDay( AUGUST_21_2023 ) ).toBeTruthy();
	} );

	it( 'should error out when passed an invalid date', () => {
		expect( () => isTodayCodeFreezeDay( 'invalid date' ) ).toThrow();
	} );
} );
