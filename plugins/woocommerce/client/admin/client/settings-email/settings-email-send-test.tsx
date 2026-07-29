/**
 * External dependencies
 */
import { Button, TextControl } from '@wordpress/components';
import { Icon, check, cautionFilled } from '@wordpress/icons';
import apiFetch from '@wordpress/api-fetch';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { emailPreviewNonce } from './settings-email-preview-nonce';

export const isValidEmail = ( email: string ) => {
	const re =
		/^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
	return re.test( String( email ).toLowerCase() );
};

type SendTestEmailResponse = {
	message: string;
};

export type WPError = {
	message: string;
	code: string;
	data: {
		status: number;
	};
};

/**
 * Maps an apiFetch error into merchant-friendly copy.
 *
 * Where possible we match on stable backend error codes rather than English
 * message strings, so the mapping still works on localized sites. The two
 * branches that still rely on message matches are flagged inline — they don't
 * have stable codes to match against.
 */
export function friendlyEmailSendError( wpError: WPError ): string {
	// apiFetch can reject with non-WPError shapes (native TypeError, wrapped middleware errors); unshaped errors fall through to the generic fallback.
	const code = wpError?.code ?? '';
	const message = wpError?.message ?? '';

	// Covers both WP core (rest_cookie_invalid_nonce) and Woo's own
	// EmailPreviewRestController check (invalid_nonce).
	if ( code === 'rest_cookie_invalid_nonce' || code === 'invalid_nonce' ) {
		return __(
			'Your session expired. Refresh the page and try again.',
			'woocommerce'
		);
	}

	// Stable WP core code for a non-JSON response body.
	if ( code === 'rest_invalid_json' ) {
		return __(
			'The server returned unexpected output. Check your error log, or disable recently added plugins.',
			'woocommerce'
		);
	}

	// Locale-fragile: WSOD responses don't carry a structured error code,
	// so we fall back to matching the English phrase PHP prints.
	if ( message.includes( 'critical error' ) ) {
		return __(
			'A PHP error stopped the send. Check your error log or contact your host.',
			'woocommerce'
		);
	}

	// Stable Woo code emitted by EmailPreviewRestController when the preview
	// template fails to render.
	if ( code === 'woocommerce_rest_email_preview_not_rendered' ) {
		return __(
			"The email couldn't be rendered. Try resetting the template in Settings → Emails.",
			'woocommerce'
		);
	}

	// Locale-fragile: this apiFetch client fallback has no stable code, so
	// we compare against the English message directly.
	if ( message === 'Could not get a valid response from the server.' ) {
		return __(
			'Your server timed out. If it keeps happening, ask your host to check PHP execution limits.',
			'woocommerce'
		);
	}

	return __(
		"Couldn't send the test email. Check your email settings and try again.",
		'woocommerce'
	);
}

/**
 * Where the send-test-email UI was opened from. Added to the Tracks payload so
 * sends from the email preview and from the emails list can be told apart.
 */
export type SendTestEmailSource = 'email_preview' | 'email_listing';

/**
 * Which backend renders and sends the test email.
 *
 * - `settings`: the wc-admin-email send-preview endpoint, driven by the exact
 *   WC_Email class name (`emailType`). Used by the legacy email preview page.
 * - `editor`: the email editor's send_preview_email endpoint, driven by the
 *   `woo_email` post ID — the exact pipeline the block email editor's own
 *   "Send a test email" modal uses. `emailType` is only used for Tracks.
 */
export type SendTestEmailTarget =
	| { endpoint: 'settings'; emailType: string }
	| { endpoint: 'editor'; postId: number; emailType: string };

/**
 * State and send logic for the "Send a test email" flow, shared between the
 * email preview page and the emails list.
 *
 * @param target Backend to send through, see SendTestEmailTarget.
 * @param source Where the UI was opened from, for Tracks.
 */
export const useSendTestEmail = (
	target: SendTestEmailTarget,
	source: SendTestEmailSource
) => {
	const [ email, setEmail ] = useState( '' );
	const [ isSending, setIsSending ] = useState( false );
	const [ notice, setNotice ] = useState( '' );
	const [ noticeType, setNoticeType ] = useState( '' );

	const sendEmail = async () => {
		setIsSending( true );
		setNotice( '' );

		try {
			if ( target.endpoint === 'editor' ) {
				await apiFetch( {
					path: '/woocommerce-email-editor/v1/send_preview_email',
					method: 'POST',
					data: { email, postId: target.postId },
				} );

				setNotice(
					__( 'Test email sent successfully!', 'woocommerce' )
				);
			} else {
				const response: SendTestEmailResponse = await apiFetch( {
					path: `wc-admin-email/settings/email/send-preview?nonce=${ emailPreviewNonce() }`,
					method: 'POST',
					data: { email, type: target.emailType },
				} );

				setNotice( response.message );
			}
			setNoticeType( 'success' );

			recordEvent( 'settings_emails_preview_test_sent_successful', {
				email_type: target.emailType,
				source,
			} );
		} catch ( e ) {
			const wpError = e as WPError;

			setNotice( friendlyEmailSendError( wpError ) );
			setNoticeType( 'error' );

			recordEvent( 'settings_emails_preview_test_sent_failed', {
				email_type: target.emailType,
				// apiFetch can reject with non-WPError shapes (e.g. a native
				// TypeError), so guard against missing fields.
				error: wpError?.message ?? '',
				error_code: wpError?.code ?? '',
				source,
			} );
		}

		setIsSending( false );
	};

	return {
		email,
		setEmail,
		isSending,
		setIsSending,
		notice,
		noticeType,
		sendEmail,
	};
};

type SendTestEmailFormProps = {
	email: string;
	onEmailChange: ( email: string ) => void;
	isSending: boolean;
	notice: string;
	noticeType: string;
	onSend: () => void;
	onCancel: () => void;
};

/**
 * Modal body of the "Send a test email" flow: recipient field, result notice,
 * and Cancel/Send buttons. Controlled — pair with `useSendTestEmail`.
 */
export const SendTestEmailForm = ( {
	email,
	onEmailChange,
	isSending,
	notice,
	noticeType,
	onSend,
	onCancel,
}: SendTestEmailFormProps ) => {
	return (
		<form
			onSubmit={ ( e ) => {
				e.preventDefault();
				if ( ! isValidEmail( email ) || isSending ) {
					return;
				}
				onSend();
			} }
		>
			<p>
				{ __(
					'Send yourself a test email to check how your email looks in different email apps.',
					'woocommerce'
				) }
			</p>

			<TextControl
				label={ __( 'Send to', 'woocommerce' ) }
				type="email"
				value={ email }
				placeholder={ __( 'Enter an email', 'woocommerce' ) }
				onChange={ onEmailChange }
			/>

			{ notice && (
				<div
					role={ noticeType === 'error' ? 'alert' : 'status' }
					className={ `wc-settings-email-preview-send-modal-notice wc-settings-email-preview-send-modal-notice-${ noticeType }` }
				>
					<Icon
						icon={
							noticeType === 'success' ? check : cautionFilled
						}
					/>
					<span>{ notice }</span>
				</div>
			) }

			<div className="wc-settings-email-preview-send-modal-buttons">
				<Button variant="tertiary" onClick={ onCancel }>
					{ __( 'Cancel', 'woocommerce' ) }
				</Button>

				<Button
					type="submit"
					variant="primary"
					isBusy={ isSending }
					disabled={ ! isValidEmail( email ) || isSending }
				>
					{ isSending
						? __( 'Sending…', 'woocommerce' )
						: __( 'Send test email', 'woocommerce' ) }
				</Button>
			</div>
		</form>
	);
};
