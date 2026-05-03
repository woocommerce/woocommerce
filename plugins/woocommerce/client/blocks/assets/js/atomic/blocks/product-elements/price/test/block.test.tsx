/**
 * External dependencies
 */
import type { Currency } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import { convertAdminPriceToStoreApiFormat } from '../block';

const createMockCurrency = ( minorUnit: number ): Currency => ( {
	code: 'USD',
	decimalSeparator: '.',
	minorUnit,
	prefix: '$',
	suffix: '',
	symbol: '$',
	thousandSeparator: ',',
} );

describe( 'convertAdminPriceToStoreApiFormat', () => {
	const currencyWithTwoDecimals = createMockCurrency( 2 );
	const currencyWithThreeDecimals = createMockCurrency( 3 );

	describe( 'basic conversion', () => {
		test( 'should convert decimal price to minor units', () => {
			expect(
				convertAdminPriceToStoreApiFormat(
					'12.99',
					currencyWithTwoDecimals
				)
			).toBe( '1299' );
		} );

		test( 'should handle whole numbers', () => {
			expect(
				convertAdminPriceToStoreApiFormat(
					'10',
					currencyWithTwoDecimals
				)
			).toBe( '1000' );
		} );

		test( 'should handle zero', () => {
			expect(
				convertAdminPriceToStoreApiFormat(
					'0',
					currencyWithTwoDecimals
				)
			).toBe( '0' );
		} );
	} );

	describe( 'floating-point precision', () => {
		test( 'should correctly round 10.04 to 1004 (not 1003)', () => {
			// This is the bug fix - without Math.round, floating point errors
			// cause 10.04 * 100 = 1003.9999999999999 -> "1003"
			expect(
				convertAdminPriceToStoreApiFormat(
					'10.04',
					currencyWithTwoDecimals
				)
			).toBe( '1004' );
		} );

		test( 'should correctly handle 0.01', () => {
			expect(
				convertAdminPriceToStoreApiFormat(
					'0.01',
					currencyWithTwoDecimals
				)
			).toBe( '1' );
		} );

		test( 'should correctly handle 9.99', () => {
			expect(
				convertAdminPriceToStoreApiFormat(
					'9.99',
					currencyWithTwoDecimals
				)
			).toBe( '999' );
		} );

		test( 'should correctly handle 19.95', () => {
			expect(
				convertAdminPriceToStoreApiFormat(
					'19.95',
					currencyWithTwoDecimals
				)
			).toBe( '1995' );
		} );
	} );

	describe( 'null and undefined handling', () => {
		test( 'should use fallback for null', () => {
			expect(
				convertAdminPriceToStoreApiFormat(
					null,
					currencyWithTwoDecimals
				)
			).toBe( '0' );
		} );

		test( 'should use fallback for undefined', () => {
			expect(
				convertAdminPriceToStoreApiFormat(
					undefined,
					currencyWithTwoDecimals
				)
			).toBe( '0' );
		} );

		test( 'should use custom fallback', () => {
			expect(
				convertAdminPriceToStoreApiFormat(
					null,
					currencyWithTwoDecimals,
					'100'
				)
			).toBe( '10000' );
		} );
	} );

	describe( 'different currency minor units', () => {
		test( 'should handle 3 decimal places (e.g., Kuwaiti Dinar)', () => {
			expect(
				convertAdminPriceToStoreApiFormat(
					'12.345',
					currencyWithThreeDecimals
				)
			).toBe( '12345' );
		} );

		test( 'should handle 0 decimal places (e.g., Japanese Yen)', () => {
			const currencyWithNoDecimals = createMockCurrency( 0 );
			expect(
				convertAdminPriceToStoreApiFormat(
					'1000',
					currencyWithNoDecimals
				)
			).toBe( '1000' );
		} );
	} );

	describe( 'edge cases', () => {
		test( 'should handle very small decimals', () => {
			expect(
				convertAdminPriceToStoreApiFormat(
					'0.001',
					currencyWithTwoDecimals
				)
			).toBe( '0' ); // Rounds to 0
		} );

		test( 'should handle large numbers', () => {
			expect(
				convertAdminPriceToStoreApiFormat(
					'9999999.99',
					currencyWithTwoDecimals
				)
			).toBe( '999999999' );
		} );

		test( 'should handle trailing zeros', () => {
			expect(
				convertAdminPriceToStoreApiFormat(
					'10.00',
					currencyWithTwoDecimals
				)
			).toBe( '1000' );
		} );
	} );
} );
