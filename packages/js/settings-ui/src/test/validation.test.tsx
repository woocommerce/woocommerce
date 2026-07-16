/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { act } from 'react';
import { createRoot } from 'react-dom/client';

/**
 * Internal dependencies
 */
import { registerSettingsExtension, __resetRegistry } from '../registry';
import { SettingsUIPage } from '../settings-ui-page';
import type { SettingsUISchema } from '../types';

globalThis.IS_REACT_ACT_ENVIRONMENT = true;

const changeTextInput = ( input: HTMLInputElement, value: string ) => {
	const valueSetter = Object.getOwnPropertyDescriptor(
		HTMLInputElement.prototype,
		'value'
	)?.set;
	valueSetter?.call( input, value );
	input.dispatchEvent(
		new Event( 'input', { bubbles: true, cancelable: true } )
	);
};

describe( 'Settings UI DataForm validation', () => {
	beforeEach( () => {
		__resetRegistry();
	} );

	it( 'shows field errors and disables save for an invalid value', async () => {
		const validator = jest.fn( ( { value } ) =>
			value === 'blocked' ? 'This value is blocked.' : null
		);
		registerSettingsExtension( {
			scope: { page: 'validation', section: '' },
			validators: { blocked: validator },
		} );
		const schema: SettingsUISchema = {
			id: 'validation',
			section: 'default',
			save: { adapter: 'form_post' },
			groups: {
				general: {
					id: 'general',
					title: 'General',
					fields: [
						{
							id: 'validated',
							label: 'Validated',
							type: 'text',
							value: 'allowed',
							validation: { validator: 'blocked' },
						},
					],
				},
			},
		};
		const container = document.createElement( 'div' );
		document.body.appendChild( container );
		const root = createRoot( container );

		await act( async () => {
			root.render( createElement( SettingsUIPage, { schema } ) );
		} );
		const input = container.querySelector(
			'input:not([type="hidden"])'
		) as HTMLInputElement;

		await act( async () => {
			input.focus();
			changeTextInput( input, 'blocked' );
			input.blur();
			await new Promise( ( resolve ) => setTimeout( resolve, 0 ) );
		} );
		await act( async () => {
			await new Promise( ( resolve ) => setTimeout( resolve, 0 ) );
		} );

		expect( validator ).toHaveBeenLastCalledWith(
			expect.objectContaining( { value: 'blocked' } )
		);
		expect( container.textContent ).toContain( 'This value is blocked.' );
		expect(
			container.querySelector( '.woocommerce-save-button' )
		).toBeDisabled();

		act( () => root.unmount() );
		container.remove();
	} );
} );
