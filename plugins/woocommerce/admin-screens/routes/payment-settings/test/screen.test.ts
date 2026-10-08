/**
 * Internal dependencies
 */
import { applyFieldModules, toError } from '../screen';
import type { FieldsResponse } from '../screen';

const response: FieldsResponse = {
	fields: [
		{ id: 'title', type: 'text', label: 'Title' },
		{ id: 'email', type: 'email', label: 'Email' },
	],
	script_modules: [
		{ id: 'first', fields: [ 'title' ] },
		{ id: 'second', fields: [ 'title', 'email' ] },
	],
};

describe( 'applyFieldModules', () => {
	it( 'applies modules in the order the server lists them', () => {
		const { fields, errors } = applyFieldModules( response, [
			{
				status: 'fulfilled',
				value: { default: { title: { description: 'First' } } },
			},
			{
				status: 'fulfilled',
				value: { default: { title: { description: 'Second' } } },
			},
		] );

		expect( errors ).toEqual( [] );
		expect( fields[ 0 ]?.description ).toBe( 'Second' );
	} );

	it( 'reports a module that failed to load and keeps the other fields', () => {
		const { fields, errors } = applyFieldModules( response, [
			{ status: 'rejected', reason: new Error( 'Network error' ) },
			{
				status: 'fulfilled',
				value: { default: { email: { description: 'Help' } } },
			},
		] );

		expect( errors ).toHaveLength( 1 );
		expect( errors[ 0 ]?.message ).toContain( 'first' );
		expect( fields.map( ( field ) => field.id ) ).toEqual( [
			'title',
			'email',
		] );
		expect( fields[ 1 ]?.description ).toBe( 'Help' );
	} );

	it( 'ignores parts for fields the module was not registered with', () => {
		const { fields } = applyFieldModules(
			{
				...response,
				script_modules: [ { id: 'first', fields: [ 'title' ] } ],
			},
			[
				{
					status: 'fulfilled',
					value: { default: { email: { description: 'Ignored' } } },
				},
			]
		);

		expect( fields[ 1 ]?.description ).toBeUndefined();
	} );
} );

describe( 'toError', () => {
	it( 'keeps an Error as it is', () => {
		const error = new Error( 'Network error' );
		expect( toError( error ) ).toBe( error );
	} );

	it( 'uses the message of a REST error object', () => {
		expect(
			toError( {
				code: 'rest_forbidden',
				message: 'Sorry, you are not allowed to do that.',
			} ).message
		).toBe( 'Sorry, you are not allowed to do that.' );
	} );

	it( 'falls back to a generic message for anything else', () => {
		expect( toError( 'oops' ).message ).toBe( 'Unable to load settings.' );
		expect( toError( { code: 'no_message' } ).message ).toBe(
			'Unable to load settings.'
		);
	} );
} );
