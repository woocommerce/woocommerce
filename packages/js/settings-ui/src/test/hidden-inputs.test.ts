/**
 * Internal dependencies
 */
import { getHiddenInputs } from '../hidden-inputs';

describe( 'getHiddenInputs', () => {
	it( 'serializes checkbox values for legacy form posts', () => {
		const checkboxField = {
			id: 'enabled',
			label: 'Enabled',
			type: 'checkbox',
			save: { adapter: 'form_post' as const, name: 'enabled' },
		};

		expect( getHiddenInputs( checkboxField, true ) ).toEqual( [
			{ name: 'enabled', value: 'yes' },
		] );
		// Every value wc_string_to_bool() treats as true serializes back
		// as yes, so an untouched save cannot flip the setting off.
		expect( getHiddenInputs( checkboxField, 1 ) ).toEqual( [
			{ name: 'enabled', value: 'yes' },
		] );
		expect( getHiddenInputs( checkboxField, 'true' ) ).toEqual( [
			{ name: 'enabled', value: 'yes' },
		] );
		expect( getHiddenInputs( checkboxField, 'no' ) ).toEqual( [
			{ name: 'enabled', value: 'no' },
		] );
	} );

	it( 'serializes array values with bracketed field names', () => {
		expect(
			getHiddenInputs(
				{
					id: 'methods',
					label: 'Methods',
					type: 'array',
					save: { adapter: 'form_post', name: 'methods' },
				},
				[ 'card', 'link' ]
			)
		).toEqual( [
			{ name: 'methods[]', value: 'card' },
			{ name: 'methods[]', value: 'link' },
		] );
	} );

	it( 'does not serialize fields using the none adapter', () => {
		expect(
			getHiddenInputs(
				{
					id: 'info',
					label: 'Info',
					type: 'info',
					save: { adapter: 'none' },
				},
				''
			)
		).toEqual( [] );
	} );
} );
