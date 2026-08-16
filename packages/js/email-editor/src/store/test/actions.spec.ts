/**
 * External dependencies
 */
import { select } from '@wordpress/data';
import { apiFetch } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import { requestSendingNewsletterPreview } from '../actions';
import { storeName } from '../constants';
import { SendingPreviewStatus } from '../types';

jest.mock( '@wordpress/data', () => ( {
	select: jest.fn(),
} ) );

jest.mock( '@wordpress/core-data', () => ( {
	store: { name: 'core' },
} ) );

jest.mock( '@wordpress/data-controls', () => ( {
	apiFetch: jest.fn(),
} ) );

jest.mock( '../../events', () => ( {
	recordEvent: jest.fn(),
} ) );

const selectMock = select as jest.Mock;
const apiFetchMock = apiFetch as jest.Mock;

const initialSendingState = {
	type: 'CHANGE_PREVIEW_STATE',
	state: {
		sendingPreviewStatus: null,
		isSendingPreviewEmail: true,
	},
};

describe( 'requestSendingNewsletterPreview', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		selectMock
			.mockReturnValueOnce( {
				getPreviewState: () => ( {
					isSendingPreviewEmail: false,
				} ),
			} )
			.mockReturnValueOnce( {
				getEmailPostId: () => 123,
			} );
	} );

	it( 'transitions to sending, posts the preview request, then marks it successful', () => {
		const request = { type: 'API_FETCH' };
		apiFetchMock.mockReturnValue( request );

		const action = requestSendingNewsletterPreview( 'test@example.com' );

		expect( action.next() ).toStrictEqual( {
			value: initialSendingState,
			done: false,
		} );
		expect( action.next() ).toStrictEqual( {
			value: request,
			done: false,
		} );
		expect( selectMock ).toHaveBeenNthCalledWith( 1, storeName );
		expect( selectMock ).toHaveBeenNthCalledWith( 2, storeName );
		expect( apiFetchMock ).toHaveBeenCalledWith( {
			path: '/woocommerce-email-editor/v1/send_preview_email',
			method: 'POST',
			data: {
				email: 'test@example.com',
				postId: 123,
			},
		} );
		expect( action.next() ).toStrictEqual( {
			value: {
				type: 'CHANGE_PREVIEW_STATE',
				state: {
					sendingPreviewStatus: SendingPreviewStatus.SUCCESS,
					isSendingPreviewEmail: false,
				},
			},
			done: false,
		} );
	} );

	it( 'transitions to the exact error state when the preview request is rejected', () => {
		const request = { type: 'API_FETCH' };
		apiFetchMock.mockReturnValue( request );
		const action = requestSendingNewsletterPreview( 'test@example.com' );

		action.next();
		action.next();

		expect( action.throw( { error: 'Request failed' } ) ).toStrictEqual( {
			value: {
				type: 'CHANGE_PREVIEW_STATE',
				state: {
					sendingPreviewStatus: SendingPreviewStatus.ERROR,
					isSendingPreviewEmail: false,
					errorMessage: '"Request failed"',
				},
			},
			done: false,
		} );
	} );
} );
