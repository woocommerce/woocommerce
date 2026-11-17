/**
 * External dependencies
 */
import { render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { HeaderSave } from '../ai-shopping-header';

describe( 'HeaderSave', () => {
	beforeEach( () => {
		document.body.innerHTML =
			'<form id="mainform"><p class="submit"><button name="save" disabled class="woocommerce-save-button" type="submit" value="Save changes">Save changes</button></p></form>';
	} );

	afterEach( () => {
		window.onbeforeunload = null;
	} );

	it( 'renders nothing and leaves the page alone when the form has no Save button', () => {
		document.body.innerHTML = '<form id="mainform"></form>';
		const { container } = render( <HeaderSave />, {
			container: document.body.appendChild(
				document.createElement( 'div' )
			),
		} );

		expect( container.querySelector( 'button' ) ).toBeNull();
	} );

	it( 'moves the form’s own Save changes button into the header as Save, still submitting the form', () => {
		const { container } = render( <HeaderSave />, {
			container: document.body.appendChild(
				document.createElement( 'div' )
			),
		} );
		const button = container.querySelector( 'button' );

		expect( button ).not.toBeNull();
		expect( button?.textContent ).toBe( 'Save' );
		expect( button?.getAttribute( 'form' ) ).toBe( 'mainform' );
		expect( button?.disabled ).toBe( true );
		expect( document.querySelector( '#mainform button' ) ).toBeNull();
	} );

	it( 'lets the page unload without the unsaved changes prompt when Save is clicked', () => {
		const { container } = render( <HeaderSave />, {
			container: document.body.appendChild(
				document.createElement( 'div' )
			),
		} );
		window.onbeforeunload = () => 'unsaved';
		const button = container.querySelector( 'button' )!;
		button.disabled = false;
		button.addEventListener( 'click', ( event ) => event.preventDefault() );

		button.click();

		expect( window.onbeforeunload ).toBeNull();
	} );

	it( 'puts the button back under the form, as Save changes, when the header goes', () => {
		const { unmount } = render( <HeaderSave />, {
			container: document.body.appendChild(
				document.createElement( 'div' )
			),
		} );

		unmount();

		const button = document.querySelector( '#mainform p.submit button' );
		expect( button?.textContent ).toBe( 'Save changes' );
	} );
} );
