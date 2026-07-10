/**
 * Tests for the shared "Send a test email" flow (useSendTestEmail +
 * SendTestEmailForm) used by both the email preview page and the emails list.
 */

/**
 * External dependencies
 */
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import {
	SendTestEmailForm,
	useSendTestEmail,
	type SendTestEmailSource,
	type SendTestEmailTarget,
} from '../settings-email-send-test';

jest.mock( '@wordpress/api-fetch', () => jest.fn() );

const recordEventMock = jest.fn();
jest.mock( '@woocommerce/tracks', () => ( {
	recordEvent: ( name: string, payload: Record< string, unknown > ) =>
		recordEventMock( name, payload ),
} ) );

jest.mock( '~/utils/admin-settings', () => ( {
	getAdminSetting: () => 'test-nonce',
} ) );

const apiFetchMock = apiFetch as unknown as jest.Mock;

const Harness = ( {
	target,
	source,
	onCancel = () => {},
}: {
	target: SendTestEmailTarget;
	source: SendTestEmailSource;
	onCancel?: () => void;
} ) => {
	const { email, setEmail, isSending, notice, noticeType, sendEmail } =
		useSendTestEmail( target, source );

	return (
		<SendTestEmailForm
			email={ email }
			onEmailChange={ setEmail }
			isSending={ isSending }
			notice={ notice }
			noticeType={ noticeType }
			onSend={ sendEmail }
			onCancel={ onCancel }
		/>
	);
};

const editorTarget: SendTestEmailTarget = {
	endpoint: 'editor',
	postId: 123,
	emailType: 'new_order',
};

const settingsTarget: SendTestEmailTarget = {
	endpoint: 'settings',
	emailType: 'WC_Email_New_Order',
};

const enterEmailAndSend = ( address: string ) => {
	fireEvent.change( screen.getByLabelText( 'Send to' ), {
		target: { value: address },
	} );
	fireEvent.click(
		screen.getByRole( 'button', { name: 'Send test email' } )
	);
};

describe( 'useSendTestEmail + SendTestEmailForm', () => {
	beforeEach( () => {
		apiFetchMock.mockReset();
		recordEventMock.mockClear();
	} );

	it( 'disables the send button until a valid email is entered', () => {
		render( <Harness target={ editorTarget } source="email_listing" /> );

		const sendButton = screen.getByRole( 'button', {
			name: 'Send test email',
		} );
		expect( sendButton ).toBeDisabled();

		fireEvent.change( screen.getByLabelText( 'Send to' ), {
			target: { value: 'not-an-email' },
		} );
		expect( sendButton ).toBeDisabled();

		fireEvent.change( screen.getByLabelText( 'Send to' ), {
			target: { value: 'merchant@example.com' },
		} );
		expect( sendButton ).toBeEnabled();
	} );

	it( 'editor target: posts the post ID to the email editor endpoint and shows the success notice', async () => {
		apiFetchMock.mockResolvedValue( { success: true, result: true } );

		render( <Harness target={ editorTarget } source="email_listing" /> );

		enterEmailAndSend( 'merchant@example.com' );

		await waitFor( () =>
			expect(
				screen.getByText( 'Test email sent successfully!' )
			).toBeInTheDocument()
		);

		expect( apiFetchMock ).toHaveBeenCalledWith( {
			path: '/woocommerce-email-editor/v1/send_preview_email',
			method: 'POST',
			data: {
				email: 'merchant@example.com',
				postId: 123,
			},
		} );
		expect( recordEventMock ).toHaveBeenCalledWith(
			'settings_emails_preview_test_sent_successful',
			{
				email_type: 'new_order',
				source: 'email_listing',
			}
		);
	} );

	it( 'settings target: posts the email type class name to the send-preview endpoint and shows the response message', async () => {
		apiFetchMock.mockResolvedValue( { message: 'Test email sent.' } );

		render( <Harness target={ settingsTarget } source="email_preview" /> );

		enterEmailAndSend( 'merchant@example.com' );

		await waitFor( () =>
			expect( screen.getByText( 'Test email sent.' ) ).toBeInTheDocument()
		);

		expect( apiFetchMock ).toHaveBeenCalledWith( {
			path: 'wc-admin-email/settings/email/send-preview?nonce=test-nonce',
			method: 'POST',
			data: {
				email: 'merchant@example.com',
				type: 'WC_Email_New_Order',
			},
		} );
		expect( recordEventMock ).toHaveBeenCalledWith(
			'settings_emails_preview_test_sent_successful',
			{
				email_type: 'WC_Email_New_Order',
				source: 'email_preview',
			}
		);
	} );

	it( 'shows the friendly error message and records the failure with its source', async () => {
		apiFetchMock.mockRejectedValue( {
			code: 'rest_cookie_invalid_nonce',
			message: 'Cookie check failed',
			data: { status: 403 },
		} );

		render( <Harness target={ editorTarget } source="email_listing" /> );

		enterEmailAndSend( 'merchant@example.com' );

		await waitFor( () =>
			expect(
				screen.getByText(
					'Your session expired. Refresh the page and try again.'
				)
			).toBeInTheDocument()
		);

		expect( recordEventMock ).toHaveBeenCalledWith(
			'settings_emails_preview_test_sent_failed',
			{
				email_type: 'new_order',
				error: 'Cookie check failed',
				error_code: 'rest_cookie_invalid_nonce',
				source: 'email_listing',
			}
		);
	} );

	it( 'shows the busy "Sending…" state while the request is in flight', async () => {
		let resolveRequest: ( value: unknown ) => void = () => {};
		apiFetchMock.mockImplementation(
			() =>
				new Promise( ( resolve ) => {
					resolveRequest = resolve;
				} )
		);

		render( <Harness target={ editorTarget } source="email_listing" /> );

		enterEmailAndSend( 'merchant@example.com' );

		const sendingButton = await screen.findByRole( 'button', {
			name: 'Sending…',
		} );
		expect( sendingButton ).toBeDisabled();

		resolveRequest( { success: true, result: true } );

		await waitFor( () =>
			expect(
				screen.getByRole( 'button', { name: 'Send test email' } )
			).toBeEnabled()
		);
	} );

	it( 'submits the form when Enter is pressed in the email field, but not while the email is invalid', async () => {
		apiFetchMock.mockResolvedValue( { success: true, result: true } );

		render( <Harness target={ editorTarget } source="email_listing" /> );

		const emailField = screen.getByLabelText( 'Send to' );

		fireEvent.change( emailField, {
			target: { value: 'not-an-email' },
		} );
		fireEvent.submit( emailField );
		expect( apiFetchMock ).not.toHaveBeenCalled();

		fireEvent.change( emailField, {
			target: { value: 'merchant@example.com' },
		} );
		fireEvent.submit( emailField );

		await waitFor( () =>
			expect(
				screen.getByText( 'Test email sent successfully!' )
			).toBeInTheDocument()
		);
	} );

	it( 'invokes onCancel when the cancel button is clicked', () => {
		const onCancel = jest.fn();
		render(
			<Harness
				target={ editorTarget }
				source="email_listing"
				onCancel={ onCancel }
			/>
		);

		fireEvent.click( screen.getByRole( 'button', { name: 'Cancel' } ) );

		expect( onCancel ).toHaveBeenCalled();
	} );
} );
