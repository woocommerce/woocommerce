/**
 * External dependencies
 */
import { select, dispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { hasNoticesOfType, removeNoticesByStatus } from '../notices';

jest.mock( '@wordpress/data' );

describe( 'Notice utils', () => {
	beforeEach( () => {
		jest.resetAllMocks();
	} );
	describe( 'hasNoticesOfType', () => {
		it( 'Correctly returns if there are notices of a given type in the core data store', () => {
			select.mockReturnValue( {
				getNotices: jest.fn().mockReturnValue( [
					{
						id: 'coupon-form',
						status: 'error',
						content:
							'Coupon cannot be removed because it is not already applied to the cart.',
						spokenMessage:
							'Coupon cannot be removed because it is not already applied to the cart.',
						isDismissible: true,
						actions: [],
						type: 'default',
						icon: null,
						explicitDismiss: false,
					},
				] ),
			} );
			const hasSnackbarNotices = hasNoticesOfType(
				'wc/cart',
				'snackbar'
			);
			const hasDefaultNotices = hasNoticesOfType( 'wc/cart', 'default' );
			expect( hasDefaultNotices ).toBe( true );
			expect( hasSnackbarNotices ).toBe( false );
		} );

		it( 'Handles notices being empty', () => {
			select.mockReturnValue( {
				getNotices: jest.fn().mockReturnValue( [] ),
			} );
			const hasDefaultNotices = hasNoticesOfType( 'wc/cart', 'default' );
			expect( hasDefaultNotices ).toBe( false );
		} );
	} );
	describe( 'removeNoticesByStatus', () => {
		it( 'Correctly removes notices of a given status', () => {
			select.mockReturnValue( {
				getNotices: jest.fn().mockReturnValue( [
					{
						id: 'coupon-form',
						status: 'error',
						content:
							'Coupon cannot be removed because it is not already applied to the cart.',
						spokenMessage:
							'Coupon cannot be removed because it is not already applied to the cart.',
						isDismissible: true,
						actions: [],
						type: 'default',
						icon: null,
						explicitDismiss: false,
					},
					{
						id: 'address-form',
						status: 'error',
						content: 'Address invalid',
						spokenMessage: 'Address invalid',
						isDismissible: true,
						actions: [],
						type: 'default',
						icon: null,
						explicitDismiss: false,
					},
					{
						id: 'some-warning',
						status: 'warning',
						content: 'Warning notice.',
						spokenMessage: 'Warning notice.',
						isDismissible: true,
						actions: [],
						type: 'default',
						icon: null,
						explicitDismiss: false,
					},
				] ),
			} );
			dispatch.mockReturnValue( {
				removeNotice: jest.fn(),
			} );
			removeNoticesByStatus( 'error' );
			expect( dispatch().removeNotice ).toHaveBeenNthCalledWith(
				1,
				'coupon-form',
				''
			);
			expect( dispatch().removeNotice ).toHaveBeenNthCalledWith(
				2,
				'address-form',
				''
			);
		} );

		it( 'Handles notices being empty', () => {
			select.mockReturnValue( {
				getNotices: jest.fn().mockReturnValue( [] ),
			} );

			dispatch.mockReturnValue( {
				removeNotice: jest.fn(),
			} );
			removeNoticesByStatus( 'empty' );
			expect( dispatch().removeNotice ).not.toBeCalled();
		} );
	} );
} );
