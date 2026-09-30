/**
 * External dependencies
 */
import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { EmailPreviewHeader } from '../settings-email-preview-header';
import { emailPreviewNonce } from '../settings-email-preview-nonce';
import { EmailPreviewType } from '../settings-email-preview-type';

jest.mock( '@wordpress/api-fetch', () => jest.fn() );
jest.mock( '../settings-email-preview-nonce', () => ( {
	emailPreviewNonce: jest.fn(),
} ) );

const apiFetchMock = apiFetch as unknown as jest.Mock;
const emailPreviewNonceMock = emailPreviewNonce as jest.MockedFunction<
	typeof emailPreviewNonce
>;

const processingOrderType = 'WC_Email_Customer_Processing_Order';
const resetPasswordType = 'WC_Email_Customer_Reset_Password';

describe( 'Email preview controls', () => {
	afterEach( () => {
		apiFetchMock.mockReset();
		emailPreviewNonceMock.mockReset();
	} );

	it( 'selects a different preview type', async () => {
		const setEmailType = jest.fn();

		render(
			<EmailPreviewType
				emailTypes={ [
					{ label: 'Processing order', value: processingOrderType },
					{ label: 'Reset password', value: resetPasswordType },
				] }
				emailType={ processingOrderType }
				setEmailType={ setEmailType }
			/>
		);

		const previewType = screen.getByRole( 'combobox', {
			name: 'Email preview type',
		} );
		expect( previewType ).toHaveValue( processingOrderType );

		await userEvent.selectOptions( previewType, resetPasswordType );

		expect( setEmailType ).toHaveBeenCalledTimes( 1 );
		expect( setEmailType.mock.calls[ 0 ][ 0 ] ).toBe( resetPasswordType );
	} );
} );

describe( 'Email preview header', () => {
	let settingsFixture = document.createElement( 'div' );
	let fromNameInput = document.createElement( 'input' );
	let fromAddressInput = document.createElement( 'input' );
	let subjectInput = document.createElement( 'input' );
	let unmount: undefined | ( () => void );

	const appendSettingInput = (
		id: string,
		labelText: string,
		value: string
	) => {
		const label = document.createElement( 'label' );
		const input = document.createElement( 'input' );

		label.htmlFor = id;
		label.textContent = labelText;
		input.id = id;
		input.value = value;
		settingsFixture.append( label, input );

		return input;
	};

	beforeEach( () => {
		unmount = undefined;
		settingsFixture = document.createElement( 'div' );
		settingsFixture.setAttribute( 'aria-label', 'Email settings' );
		document.body.appendChild( settingsFixture );

		fromNameInput = appendSettingInput(
			'woocommerce_email_from_name',
			'From name',
			'Acme Store'
		);
		fromAddressInput = appendSettingInput(
			'woocommerce_email_from_address',
			'From address',
			'orders@example.com'
		);
		subjectInput = appendSettingInput(
			'woocommerce_customer_processing_order_subject',
			'Email subject',
			'Order received'
		);

		emailPreviewNonceMock.mockReturnValue( 'preview-nonce' );
	} );

	afterEach( () => {
		unmount?.();
		settingsFixture.remove();
		apiFetchMock.mockReset();
		emailPreviewNonceMock.mockReset();
	} );

	it( 'updates sender values from setting change events', async () => {
		apiFetchMock.mockResolvedValue( { subject: 'Processing order' } );

		( { unmount } = render(
			<EmailPreviewHeader emailType={ processingOrderType } />
		) );

		await screen.findByRole( 'heading', { name: 'Processing order' } );
		const sender = screen.getByText( /Acme Store/ );
		expect( sender ).toHaveTextContent( 'Acme Store <orders@example.com>' );

		fireEvent.change( fromNameInput, {
			target: { value: 'Acme Warehouse' },
		} );

		await waitFor( () =>
			expect( sender ).toHaveTextContent(
				'Acme Warehouse <orders@example.com>'
			)
		);

		fireEvent.change( fromAddressInput, {
			target: { value: 'warehouse@example.com' },
		} );

		await waitFor( () =>
			expect( sender ).toHaveTextContent(
				'Acme Warehouse <warehouse@example.com>'
			)
		);
	} );

	it( 'refreshes the preview subject from settings events', async () => {
		apiFetchMock
			.mockResolvedValueOnce( { subject: 'Processing order received' } )
			.mockResolvedValueOnce( { subject: 'Updated processing order' } );
		const subjectUpdatedListener = jest.fn();
		subjectInput.addEventListener(
			'subject-updated',
			subjectUpdatedListener
		);

		try {
			( { unmount } = render(
				<EmailPreviewHeader emailType={ processingOrderType } />
			) );

			await screen.findByRole( 'heading', {
				name: 'Processing order received',
			} );
			expect( apiFetchMock ).toHaveBeenCalledWith( {
				path: `wc-admin-email/settings/email/preview-subject?type=${ processingOrderType }&nonce=preview-nonce`,
			} );
			await waitFor( () =>
				expect( subjectUpdatedListener ).toHaveBeenCalledTimes( 1 )
			);
			subjectUpdatedListener.mockClear();

			fireEvent( subjectInput, new Event( 'transient-saved' ) );

			await screen.findByRole( 'heading', {
				name: 'Updated processing order',
			} );
			expect( apiFetchMock ).toHaveBeenLastCalledWith( {
				path: `wc-admin-email/settings/email/preview-subject?type=${ processingOrderType }&nonce=preview-nonce`,
			} );
			expect( subjectUpdatedListener ).toHaveBeenCalledTimes( 1 );
		} finally {
			subjectInput.removeEventListener(
				'subject-updated',
				subjectUpdatedListener
			);
		}
	} );

	it( 'requests the preview subject once after a transient save', async () => {
		apiFetchMock.mockResolvedValue( { subject: 'Processing order' } );

		( { unmount } = render(
			<EmailPreviewHeader emailType={ processingOrderType } />
		) );

		await screen.findByRole( 'heading', { name: 'Processing order' } );
		apiFetchMock.mockClear();

		fireEvent( subjectInput, new Event( 'transient-saved' ) );

		await waitFor( () =>
			expect( apiFetchMock ).toHaveBeenCalledTimes( 1 )
		);
	} );
} );
