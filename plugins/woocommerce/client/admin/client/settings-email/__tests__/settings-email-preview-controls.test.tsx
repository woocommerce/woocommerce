/**
 * External dependencies
 */
import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import apiFetch from '@wordpress/api-fetch';
import { createSlotFill, SlotFillProvider } from '@wordpress/components';
import { registerPlugin } from '@wordpress/plugins';

/**
 * Internal dependencies
 */
import { SETTINGS_SLOT_FILL_CONSTANT } from '~/settings/settings-slots';
import { EmailPreviewHeader } from '../settings-email-preview-header';
import { emailPreviewNonce } from '../settings-email-preview-nonce';
import { EmailPreviewType } from '../settings-email-preview-type';
import {
	registerSettingsEmailPreviewFill,
	type EmailTypes,
} from '../settings-email-preview-slotfill';

jest.mock( '@wordpress/api-fetch', () => jest.fn() );
jest.mock( '@wordpress/plugins', () => ( {
	registerPlugin: jest.fn(),
} ) );
jest.mock( '../settings-email-preview-nonce', () => ( {
	emailPreviewNonce: jest.fn(),
} ) );

const apiFetchMock = apiFetch as unknown as jest.Mock;
const registerPluginMock = registerPlugin as jest.Mock;
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

describe( 'Email preview fill', () => {
	const { Slot } = createSlotFill( SETTINGS_SLOT_FILL_CONSTANT );
	const previewUrl =
		'http://example.com/wp-admin/?preview_woocommerce_mail=true';

	// Mount the fill the way WC_Settings_Emails does: print the slot element
	// with its data attributes, register the plugin, then render the plugin's
	// element into a matching slot.
	const renderPreviewFill = ( emailTypes: EmailTypes ) => {
		const mount = document.createElement( 'div' );
		mount.id = 'wc_settings_email_preview_slotfill';
		mount.setAttribute( 'data-preview-url', previewUrl );
		mount.setAttribute( 'data-email-types', JSON.stringify( emailTypes ) );
		mount.setAttribute( 'data-email-setting-ids', '[]' );
		document.body.appendChild( mount );

		registerSettingsEmailPreviewFill();

		expect( registerPluginMock ).toHaveBeenCalledTimes( 1 );
		const [ , settings ] = registerPluginMock.mock.calls[ 0 ];

		return render(
			<SlotFillProvider>
				<Slot />
				{ settings.render() }
			</SlotFillProvider>
		);
	};

	beforeEach( () => {
		apiFetchMock.mockResolvedValue( { subject: 'Preview subject' } );
		emailPreviewNonceMock.mockReturnValue( 'preview-nonce' );
	} );

	afterEach( () => {
		document.body.innerHTML = '';
		registerPluginMock.mockClear();
		apiFetchMock.mockReset();
		emailPreviewNonceMock.mockReset();
	} );

	it( 'previews the only email type without offering the type select', async () => {
		renderPreviewFill( [
			{ label: 'Reset password', value: resetPasswordType },
		] );

		expect(
			await screen.findByTitle( 'Email preview frame' )
		).toHaveAttribute(
			'src',
			`${ previewUrl }&type=${ resetPasswordType }`
		);
		expect( screen.queryByLabelText( 'Email preview type' ) ).toBeNull();
	} );

	it( 'offers the type select when more than one email type is previewable', async () => {
		renderPreviewFill( [
			{ label: 'Processing order', value: processingOrderType },
			{ label: 'Reset password', value: resetPasswordType },
		] );

		expect(
			await screen.findByTitle( 'Email preview frame' )
		).toHaveAttribute(
			'src',
			`${ previewUrl }&type=${ processingOrderType }`
		);
		expect( screen.getByLabelText( 'Email preview type' ) ).toHaveValue(
			processingOrderType
		);
	} );
} );
