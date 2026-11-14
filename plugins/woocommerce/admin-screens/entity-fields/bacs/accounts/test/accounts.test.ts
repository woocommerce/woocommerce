/**
 * Internal dependencies
 */
import { formatSortCode, hasSortCode, parseSortCode } from '../sort-code';
import { moveAccount } from '../field';
import type { BankAccount } from '../../types';

const account = ( account_name: string ): BankAccount => ( {
	account_name,
	account_number: '',
	bank_name: '',
	sort_code: '',
	iban: '',
	bic: '',
} );

describe( 'sort codes', () => {
	it( 'stores UK and Irish sort codes as six digits and shows them in pairs', () => {
		expect( parseSortCode( '12-34-567', 'GB' ) ).toBe( '123456' );
		expect( formatSortCode( '123456', 'IE' ) ).toBe( '12-34-56' );
	} );

	it( 'keeps other countries’ codes as typed', () => {
		expect( parseSortCode( '012-345', 'AU' ) ).toBe( '012-345' );
		expect( formatSortCode( '021000021', 'US' ) ).toBe( '021000021' );
	} );

	it( 'only asks for a sort code where the country has one', () => {
		expect( hasSortCode( 'US' ) ).toBe( true );
		expect( hasSortCode( 'DE' ) ).toBe( false );
	} );
} );

describe( 'moveAccount', () => {
	it( 'moves an account without changing the others’ order', () => {
		const accounts = [ account( 'A' ), account( 'B' ), account( 'C' ) ];

		expect(
			moveAccount( accounts, 2, 1 ).map( ( item ) => item.account_name )
		).toEqual( [ 'A', 'C', 'B' ] );
		expect( accounts.map( ( item ) => item.account_name ) ).toEqual( [
			'A',
			'B',
			'C',
		] );
	} );
} );
