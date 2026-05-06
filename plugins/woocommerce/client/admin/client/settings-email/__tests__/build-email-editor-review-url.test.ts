/**
 * Unit tests for the wc_email_review_drawer URL helper. RSM-140.
 */

/**
 * Internal dependencies
 */
import {
	buildEmailEditorReviewUrl,
	REVIEW_DRAWER_PARAM,
} from '../build-email-editor-review-url';

describe( 'buildEmailEditorReviewUrl', () => {
	it( 'returns a relative post.php URL with post, action, and the review-drawer param', () => {
		const url = buildEmailEditorReviewUrl( 123 );

		expect( url ).toContain( 'post.php?' );
		expect( url ).toContain( 'post=123' );
		expect( url ).toContain( 'action=edit' );
		expect( url ).toContain( `${ REVIEW_DRAWER_PARAM }=1` );
	} );

	it( 'exports the param name as a stable constant', () => {
		expect( REVIEW_DRAWER_PARAM ).toBe( 'wc_email_review_drawer' );
	} );

	it( 'throws on a non-positive id', () => {
		expect( () => buildEmailEditorReviewUrl( 0 ) ).toThrow();
		expect( () => buildEmailEditorReviewUrl( -1 ) ).toThrow();
		// @ts-expect-error: deliberately wrong type to verify guard.
		expect( () => buildEmailEditorReviewUrl( undefined ) ).toThrow();
	} );

	it( 'encodes via URLSearchParams (no hand-rolled concat)', () => {
		const url = buildEmailEditorReviewUrl( 9999 );
		expect( url ).toMatch(
			/post=9999&action=edit&wc_email_review_drawer=1$/
		);
	} );
} );
