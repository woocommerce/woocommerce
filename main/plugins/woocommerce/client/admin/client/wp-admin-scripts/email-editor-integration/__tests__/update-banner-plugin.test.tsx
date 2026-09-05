/**
 * External dependencies
 */
import { render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { UpdateBannerPlugin } from '../update-banner-plugin';

// ---- useUpdateBanner mock -------------------------------------------------
//
// Drive the plugin's render decision from the test by overriding the
// hook's return value. Each test sets `useUpdateBannerMock` to the
// shape it wants, then renders the plugin; the mock is consulted on
// render and again after the rAF in the impl, so changing it
// per-test is sufficient.
const useUpdateBannerMock = jest.fn();
jest.mock( '../hooks/use-update-banner', () => ( {
	useUpdateBanner: () => useUpdateBannerMock(),
} ) );

const baseHookReturn = {
	shouldRender: false,
	summary: null,
	isLoadingSummary: false,
	summaryError: null,
	applyState: 'idle' as const,
	canApply: true,
	canReview: true,
	disabledReason: null,
	hasConflicts: false,
	expanded: false,
	toggleExpanded: jest.fn(),
	apply: jest.fn(),
	openReview: jest.fn(),
	dismiss: jest.fn(),
	autoDismiss: jest.fn(),
};

describe( 'UpdateBannerPlugin', () => {
	beforeEach( () => {
		// Reset the DOM between tests so the canvas selector / body
		// fallback assertions stay isolated.
		document.body.innerHTML = '';
		useUpdateBannerMock.mockReset();
	} );

	it( 'returns null when shouldRender is false', () => {
		useUpdateBannerMock.mockReturnValue( {
			...baseHookReturn,
			shouldRender: false,
		} );

		const { container } = render( <UpdateBannerPlugin /> );
		expect( container.firstChild ).toBeNull();
	} );

	it( 'portals the banner into the editor canvas target when present', () => {
		const target = document.createElement( 'div' );
		target.className = 'edit-post-visual-editor';
		document.body.appendChild( target );

		useUpdateBannerMock.mockReturnValue( {
			...baseHookReturn,
			shouldRender: true,
		} );

		render( <UpdateBannerPlugin /> );

		expect( target.querySelector( '.wc-update-banner' ) ).not.toBeNull();
	} );

	it( 'falls back to document.body when no canvas target is present', () => {
		useUpdateBannerMock.mockReturnValue( {
			...baseHookReturn,
			shouldRender: true,
		} );

		render( <UpdateBannerPlugin /> );

		expect(
			document.body.querySelector( '.wc-update-banner' )
		).not.toBeNull();
	} );
} );
