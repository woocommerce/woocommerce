/**
 * External dependencies
 */
import { register, select, dispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { store as canvasModeStore } from '../index';

describe( 'Canvas Mode Store', () => {
	beforeEach( () => {
		// Reset the store before each test.
		if ( ! select( canvasModeStore ) ) {
			register( canvasModeStore );
		}
	} );

	describe( 'Initial State', () => {
		it( 'should initialize with isEditMode as false', () => {
			const state = select( canvasModeStore ).isEditMode();
			expect( state ).toBe( false );
		} );
	} );

	describe( 'URL-based Mode Detection', () => {
		it( 'should detect edit mode from URL', () => {
			const originalLocation = window.location;
			Object.defineProperty( window, 'location', {
				writable: true,
				value: new URL( 'http://example.com?canvas=edit' ),
			} );

			const state = select( canvasModeStore ).isEditMode();
			expect( state ).toBe( true );

			// Restore window.location.
			Object.defineProperty( window, 'location', {
				writable: true,
				value: originalLocation,
			} );
		} );

		it( 'should detect list mode from URL', () => {
			const originalLocation = window.location;
			Object.defineProperty( window, 'location', {
				writable: true,
				value: new URL( 'http://example.com' ),
			} );

			const state = select( canvasModeStore ).isEditMode();
			expect( state ).toBe( false );

			Object.defineProperty( window, 'location', {
				writable: true,
				value: originalLocation,
			} );
		} );

		it( 'should handle invalid URLs gracefully', () => {
			// Mock window.location with invalid URL.
			const originalLocation = window.location;
			Object.defineProperty( window, 'location', {
				writable: true,
				value: { href: 'invalid-url' } as Location,
			} );

			const state = select( canvasModeStore ).isEditMode();
			expect( state ).toBe( false );

			Object.defineProperty( window, 'location', {
				writable: true,
				value: originalLocation,
			} );
		} );
	} );

	describe( 'Mode Switching', () => {
		it( 'should update URL when switching to edit mode', () => {
			const originalLocation = window.location;
			Object.defineProperty( window, 'location', {
				writable: true,
				value: new URL( 'http://example.com' ),
			} );

			dispatch( canvasModeStore ).setCanvasMode( true );

			// Verify URL was updated
			expect( window.location.search ).toBe( '' );

			Object.defineProperty( window, 'location', {
				writable: true,
				value: originalLocation,
			} );
		} );

		it( 'should update URL when switching to list mode', () => {
			const originalLocation = window.location;
			Object.defineProperty( window, 'location', {
				writable: true,
				value: new URL( 'http://example.com?canvas=edit' ),
			} );

			dispatch( canvasModeStore ).setCanvasMode( false );

			// Verify URL was updated
			expect( window.location.search ).toBe( '?canvas=edit' );

			Object.defineProperty( window, 'location', {
				writable: true,
				value: originalLocation,
			} );
		} );
	} );
} );
