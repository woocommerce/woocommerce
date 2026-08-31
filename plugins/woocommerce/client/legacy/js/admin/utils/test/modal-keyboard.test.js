/**
 * Test for shouldConfirmModalOnEnter method from utils/modal-keyboard.js
 */

// Import the utility function
const { shouldConfirmModalOnEnter } = require('../modal-keyboard');

/**
 * Build an element, optionally inside a wrapper matching a selector.
 *
 * @param {string} tagName - Tag to create.
 * @param {string} [wrapperClass] - Class for an ancestor element.
 * @returns {HTMLElement} The created element.
 */
function element( tagName, wrapperClass ) {
	const node = document.createElement( tagName );

	if ( wrapperClass ) {
		const wrapper = document.createElement( 'div' );
		wrapper.className = wrapperClass;
		wrapper.appendChild( node );
		return node;
	}

	return node;
}

describe( 'Modal Keyboard Utils - shouldConfirmModalOnEnter', () => {
	it( 'confirms when Enter is pressed on the modal body itself', () => {
		expect( shouldConfirmModalOnEnter( element( 'div' ) ) ).toBe( true );
		expect( shouldConfirmModalOnEnter( element( 'section' ) ) ).toBe( true );
	} );

	it( 'does not confirm on controls that act on Enter themselves', () => {
		[ 'input', 'textarea', 'button', 'a', 'select' ].forEach( ( tagName ) => {
			expect( shouldConfirmModalOnEnter( element( tagName ) ) ).toBe( false );
		} );
	} );

	it( 'does not confirm on the pagination and search controls of the add tax modal', () => {
		const paginationButton = element( 'button' );
		paginationButton.className = 'next-page button';

		const searchField = element( 'input' );
		searchField.type = 'search';

		const settingsLink = element( 'a' );
		settingsLink.href = '#';

		expect( shouldConfirmModalOnEnter( paginationButton ) ).toBe( false );
		expect( shouldConfirmModalOnEnter( searchField ) ).toBe( false );
		expect( shouldConfirmModalOnEnter( settingsLink ) ).toBe( false );
	} );

	it( 'does not confirm inside an enhanced select', () => {
		expect(
			shouldConfirmModalOnEnter( element( 'span', 'select2-container' ) )
		).toBe( false );
		expect(
			shouldConfirmModalOnEnter( element( 'li', 'select2-selection' ) )
		).toBe( false );
	} );

	it( 'confirms for a plain element outside an enhanced select', () => {
		expect(
			shouldConfirmModalOnEnter( element( 'span', 'wc-backbone-modal' ) )
		).toBe( true );
	} );

	it( 'confirms when the target is missing or is not an element', () => {
		expect( shouldConfirmModalOnEnter( null ) ).toBe( true );
		expect( shouldConfirmModalOnEnter( undefined ) ).toBe( true );
		expect( shouldConfirmModalOnEnter( document ) ).toBe( true );
	} );
} );
