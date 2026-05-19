/**
 * External dependencies
 */
import { Button, Modal, TextControl } from '@wordpress/components';
import { Icon, check, warning } from '@wordpress/icons';
import apiFetch from '@wordpress/api-fetch';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { recordEvent } from '@woocommerce/tracks';
import { isValidEmail } from '@woocommerce/product-editor/build/utils/validate-email'; // Import from the build directory so we don't load the entire product editor since we only need this one function.

/**
 * Internal dependencies
 */
import { emailPreviewNonce } from './settings-email-preview-nonce';

type EmailPreviewSendProps = {
	type: string;
};

type EmailPreviewSendResponse = {
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

export const EmailPreviewSend = ( { type }: EmailPreviewSendProps ) => {
	const [ isModalOpen, setIsModalOpen ] = useState( false );
	const [ email, setEmail ] = useState( '' );
	const [ isSending, setIsSending ] = useState( false );
	const [ notice, setNotice ] = useState( '' );
	const [ noticeType, setNoticeType ] = useState( '' );

	const nonce = emailPreviewNonce();

	const handleSendEmail = async () => {
		setIsSending( true );
		setNotice( '' );

		try {
			const response: EmailPreviewSendResponse = await apiFetch( {
				path: `wc-admin-email/settings/email/send-preview?nonce=${ nonce }`,
				method: 'POST',
				data: { email, type },
			} );

			setNotice( response.message );
			setNoticeType( 'success' );

			recordEvent( 'settings_emails_preview_test_sent_successful', {
				email_type: type,
			} );
		} catch ( e ) {
			const wpError = e as WPError;

			setNotice( friendlyEmailSendError( wpError ) );
			setNoticeType( 'error' );

			recordEvent( 'settings_emails_preview_test_sent_failed', {
				email_type: type,
				error: wpError.message,
				error_code: wpError.code,
			} );
		}

		setIsSending( false );
	};

	return (
		<div className="wc-settings-email-preview-send">
			<Button
				variant="secondary"
				onClick={ () => setIsModalOpen( true ) }
			>
				{ __( 'Send a test email', 'woocommerce' ) }
			</Button>

			{ isModalOpen && (
				<Modal
					title={ __( 'Send a test email', 'woocommerce' ) }
					onRequestClose={ () => {
						setIsModalOpen( false );
						setIsSending( false );
					} }
					className="wc-settings-email-preview-send-modal"
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
						onChange={ setEmail }
					/>

					{ notice && (
						<div
							className={ `wc-settings-email-preview-send-modal-notice wc-settings-email-preview-send-modal-notice-${ noticeType }` }
						>
							<Icon
								icon={
									noticeType === 'success' ? check : warning
								}
							/>
							<span>{ notice }</span>
						</div>
					) }

					<div className="wc-settings-email-preview-send-modal-buttons">
						<Button
							variant="tertiary"
							onClick={ () => setIsModalOpen( false ) }
						>
							{ __( 'Cancel', 'woocommerce' ) }
						</Button>

						<Button
							variant="primary"
							onClick={ handleSendEmail }
							isBusy={ isSending }
							disabled={ ! isValidEmail( email ) || isSending }
						>
							{ isSending
								? __( 'Sending…', 'woocommerce' )
								: __( 'Send test email', 'woocommerce' ) }
						</Button>
					</div>
				</Modal>
			) }
		</div>
	);
};
