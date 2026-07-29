/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import type { ComponentType } from 'react';

/**
 * Internal dependencies
 */
import { ListView } from '../settings-email-listing-listview';
import type { EmailType } from '../settings-email-listing-slotfill';
import { useSendTestEmail } from '../settings-email-send-test';

jest.mock( '@wordpress/dataviews/wp', () => ( {
	DataViews: ( {
		actions,
		data,
	}: {
		actions: Array< {
			id: string;
			RenderModal?: ComponentType< {
				items: EmailType[];
				closeModal?: () => void;
			} >;
		} >;
		data: EmailType[];
	} ) => {
		const SendTestEmailModal = actions.find(
			( action ) => action.id === 'test'
		)?.RenderModal;

		return SendTestEmailModal ? (
			<SendTestEmailModal items={ data } closeModal={ () => {} } />
		) : null;
	},
} ) );

jest.mock( '../settings-email-listing-data', () => ( {
	useTransactionalEmails: ( emailTypes: EmailType[] ) => ( {
		emails: emailTypes,
		total: emailTypes.length,
		updateEmailEnabledStatus: jest.fn(),
		recreateEmailPost: jest.fn(),
	} ),
} ) );

jest.mock( '../settings-email-send-test', () => ( {
	useSendTestEmail: jest.fn( () => ( {
		email: '',
		setEmail: jest.fn(),
		isSending: false,
		notice: '',
		noticeType: '',
		sendEmail: jest.fn(),
	} ) ),
	SendTestEmailForm: () => null,
} ) );

const useSendTestEmailMock = useSendTestEmail as jest.MockedFunction<
	typeof useSendTestEmail
>;

const emailType: EmailType = {
	title: 'New order',
	description: 'New order notification',
	id: 'new_order',
	email_key: 'wc_email_new_order',
	email_class_name: 'WC_Email_New_Order',
	post_id: '123',
	recipients: {
		to: 'admin@example.com',
		cc: '',
		bcc: '',
	},
	enabled: true,
	manual: false,
	templateStatus: null,
	templateVersion: null,
	currentVersion: null,
	wasBackfilled: false,
};

describe( 'ListView', () => {
	beforeEach( () => {
		useSendTestEmailMock.mockClear();
	} );

	it( 'uses the email class name when tracking a test send', () => {
		render( <ListView emailTypes={ [ emailType ] } /> );

		expect( useSendTestEmailMock ).toHaveBeenCalledWith(
			{
				endpoint: 'editor',
				postId: 123,
				emailType: 'WC_Email_New_Order',
			},
			'email_listing'
		);
	} );
} );
