/**
 * Internal dependencies
 */
import { isTopmostOverlay, setOverlayScrolling } from '../utils/overlay';

describe( 'Product Filters overlay scrolling', () => {
	const first = document.createElement( 'div' );
	const second = document.createElement( 'div' );
	let previousStyle: string;

	beforeEach( () => {
		previousStyle = document.body.style.cssText;
	} );

	afterEach( () => {
		setOverlayScrolling( first, false );
		setOverlayScrolling( second, false );
		document.body.style.cssText = previousStyle;
	} );

	it( 'keeps scrolling locked until the last open panel closes', () => {
		document.body.style.setProperty( 'overflow', 'scroll', 'important' );
		setOverlayScrolling( first, true );
		setOverlayScrolling( first, true );
		setOverlayScrolling( second, true );

		expect( isTopmostOverlay( second ) ).toBe( true );
		expect( isTopmostOverlay( first ) ).toBe( false );
		setOverlayScrolling( first, false );
		expect( document.body.style.overflow ).toBe( 'hidden' );
		setOverlayScrolling( second, false );
		expect( document.body.style.overflow ).toBe( 'scroll' );
		expect( document.body.style.getPropertyPriority( 'overflow' ) ).toBe(
			'important'
		);
	} );

	it( 'does not change the page overflow when a closed panel initializes', () => {
		document.body.style.overflow = 'clip';
		setOverlayScrolling( first, false );
		expect( document.body.style.overflow ).toBe( 'clip' );
	} );

	it( 'removes its inline override when the page had no overflow style', () => {
		document.body.style.removeProperty( 'overflow' );
		setOverlayScrolling( first, true );
		setOverlayScrolling( first, false );
		expect( document.body.style.getPropertyValue( 'overflow' ) ).toBe( '' );
	} );
} );
