/**
 * Internal dependencies
 */
import { getAccountCountry, getShippingElements } from '../entities';
import type { BankAccount } from '../entities';

const account: BankAccount = {
	account_name: 'Merchant',
	account_number: '1234',
	bank_name: 'Bank',
	sort_code: '12-34-56',
	iban: '',
	bic: '',
};
it( 'preserves shipping instance IDs when flattening grouped choices', () => {
	expect(
		getShippingElements( {
			flat_rate: 'Any &quot;Flat rate&quot; method',
			Europe: {
				'flat_rate:3': 'Flat rate in Europe',
				'local_pickup:5': 'Local pickup in Europe',
			},
		} )
	).toEqual( [
		{ value: 'flat_rate', label: 'Any "Flat rate" method' },
		{ value: 'flat_rate:3', label: 'Flat rate in Europe' },
		{ value: 'local_pickup:5', label: 'Local pickup in Europe' },
	] );
} );

it( 'uses the store country for legacy accounts without replacing a stored account country', () => {
	expect( getAccountCountry( account, 'GB' ) ).toBe( 'GB' );
	expect( getAccountCountry( { ...account, country_code: '' }, 'GB' ) ).toBe(
		'GB'
	);
	expect(
		getAccountCountry( { ...account, country_code: 'US' }, 'GB' )
	).toBe( 'US' );
} );
