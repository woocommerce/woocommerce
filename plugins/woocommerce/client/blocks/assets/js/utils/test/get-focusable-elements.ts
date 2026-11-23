/**
 * Internal dependencies
 */
import { getFocusableElements } from '../get-focusable-elements';

function markRendered( element: HTMLElement ) {
	Object.defineProperty( element, 'offsetParent', {
		get: () => document.body,
	} );
}

describe( 'getFocusableElements', () => {
	it( 'returns nothing when the container is missing', () => {
		expect( getFocusableElements( null ) ).toEqual( [] );
		expect( getFocusableElements() ).toEqual( [] );
	} );

	it( 'returns shown controls and skips hidden, disabled, and negative tabindex elements', () => {
		const container = document.createElement( 'div' );
		const link = document.createElement( 'a' );
		link.href = 'https://example.com';
		const button = document.createElement( 'button' );
		const disabledButton = document.createElement( 'button' );
		disabledButton.disabled = true;
		const hiddenInput = document.createElement( 'input' );
		hiddenInput.type = 'hidden';
		const textInput = document.createElement( 'input' );
		const ariaHiddenSelect = document.createElement( 'select' );
		ariaHiddenSelect.setAttribute( 'aria-hidden', 'true' );
		const skipped = document.createElement( 'span' );
		skipped.tabIndex = -1;
		const custom = document.createElement( 'span' );
		custom.tabIndex = 0;

		[
			link,
			button,
			disabledButton,
			hiddenInput,
			textInput,
			ariaHiddenSelect,
			skipped,
			custom,
		].forEach( ( element ) => {
			markRendered( element );
			container.append( element );
		} );

		const hiddenButton = document.createElement( 'button' );
		container.append( hiddenButton );

		expect( getFocusableElements( container ) ).toEqual( [
			link,
			button,
			textInput,
			custom,
		] );
	} );
} );
