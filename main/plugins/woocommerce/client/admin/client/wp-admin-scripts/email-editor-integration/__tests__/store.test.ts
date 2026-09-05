/**
 * External dependencies
 */
import { dispatch, select } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { registerStore, STORE_NAME } from '../store';

describe( 'email-editor-integration store', () => {
	beforeAll( () => {
		registerStore();
	} );

	describe( 'dismiss state', () => {
		it( 'returns false for an unseen postId', () => {
			expect(
				select( STORE_NAME ).isUpdateBannerDismissedFor( 100 )
			).toBe( false );
		} );

		it( 'flips to true only for the dismissed postId', () => {
			dispatch( STORE_NAME ).dismissUpdateBanner( 101 );

			expect(
				select( STORE_NAME ).isUpdateBannerDismissedFor( 101 )
			).toBe( true );
			expect(
				select( STORE_NAME ).isUpdateBannerDismissedFor( 200 )
			).toBe( false );
		} );

		it( 'clearDismissedForPost removes the entry', () => {
			dispatch( STORE_NAME ).dismissUpdateBanner( 9001 );
			expect(
				select( STORE_NAME ).isUpdateBannerDismissedFor( 9001 )
			).toBe( true );

			dispatch( STORE_NAME ).clearDismissedForPost( 9001 );
			expect(
				select( STORE_NAME ).isUpdateBannerDismissedFor( 9001 )
			).toBe( false );
		} );

		it( 'clearing an absent postId is a no-op', () => {
			expect( () => {
				dispatch( STORE_NAME ).clearDismissedForPost( 424242 );
			} ).not.toThrow();
			expect(
				select( STORE_NAME ).isUpdateBannerDismissedFor( 424242 )
			).toBe( false );
		} );
	} );

	describe( 'viewed dedup state', () => {
		it( 'returns false for an unseen (postId, versionTo) pair', () => {
			expect(
				select( STORE_NAME ).wasUpdateBannerViewedFor( 300, '9.5' )
			).toBe( false );
		} );

		it( 'flips only the marked pair, not other versions or other posts', () => {
			dispatch( STORE_NAME ).markUpdateBannerViewed( 300, '9.5' );

			expect(
				select( STORE_NAME ).wasUpdateBannerViewedFor( 300, '9.5' )
			).toBe( true );
			// Same post, different version stays false.
			expect(
				select( STORE_NAME ).wasUpdateBannerViewedFor( 300, '9.6' )
			).toBe( false );
			// Different post, same version stays false.
			expect(
				select( STORE_NAME ).wasUpdateBannerViewedFor( 301, '9.5' )
			).toBe( false );
		} );
	} );

	describe( 'unrelated drawer state preserved', () => {
		it( 'openReviewDrawer + closeReviewDrawer still work after extensions', () => {
			dispatch( STORE_NAME ).openReviewDrawer();
			expect( select( STORE_NAME ).isReviewDrawerOpen() ).toBe( true );

			dispatch( STORE_NAME ).closeReviewDrawer();
			expect( select( STORE_NAME ).isReviewDrawerOpen() ).toBe( false );
		} );
	} );
} );
