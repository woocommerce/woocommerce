/**
 * External dependencies
 */
import { Button, Modal, TextControl } from '@wordpress/components';
import { Icon, check, warning } from '@wordpress/icons';
import apiFetch from '@wordpress/api-fetch';
import { useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
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

type WPError = {
	message: string;
	code: string;
	data: {
		status: number;
	};
};

// TODO: No sibling test file exists for this module yet. When one is added,
// cover each branch of friendlyEmailSendError plus the fallback.
function friendlyEmailSendError( wpError: WPError ): string {
	const { code, message } = wpError;

	if (
		code === 'rest_cookie_invalid_nonce' ||
		message === 'Invalid nonce.'
	) {
		return __(
			'Your session expired. Refresh the page, then try sending again.',
			'woocommerce'
		);
	}

	if (
		code === 'rest_invalid_json' ||
		message === 'The response is not a valid JSON response.'
	) {
		return __(
			'The server sent an unexpected response. A plugin on your site is probably printing PHP warnings. Check your error log, or try disabling recently added plugins.',
			'woocommerce'
		);
	}

	if ( message.includes( 'critical error' ) ) {
		return __(
			"A PHP error stopped the test email. Check your site's error log, or contact your host. A recently added plugin is often the cause.",
			'woocommerce'
		);
	}

	if ( message === 'There was an error rendering an email preview.' ) {
		return __(
			"The email couldn't be rendered. A customization or plugin may be interfering with this template. Try resetting it to default in Settings → Emails.",
			'woocommerce'
		);
	}

	if ( message === 'Could not get a valid response from the server.' ) {
		return __(
			"Your server didn't respond in time. Try again in a moment. If it keeps happening, ask your host to check your PHP execution limits.",
			'woocommerce'
		);
	}

	const cleanMessage = message.replace( /\.$/, '' );
	return sprintf(
		// translators: %s is the raw error message from the server.
		__(
			"We couldn't send the test email: %s. Try again, or review your email settings if the problem continues.",
			'woocommerce'
		),
		cleanMessage
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
