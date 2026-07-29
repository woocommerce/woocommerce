/**
 * External dependencies
 */
import { Button, Modal } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import {
	SendTestEmailForm,
	useSendTestEmail,
} from './settings-email-send-test';

// Re-exported so existing consumers (and tests) keep working after the send
// logic moved to settings-email-send-test.tsx.
export {
	friendlyEmailSendError,
	isValidEmail,
	type WPError,
} from './settings-email-send-test';

type EmailPreviewSendProps = {
	type: string;
};

export const EmailPreviewSend = ( { type }: EmailPreviewSendProps ) => {
	const [ isModalOpen, setIsModalOpen ] = useState( false );
	const {
		email,
		setEmail,
		isSending,
		setIsSending,
		notice,
		noticeType,
		sendEmail,
	} = useSendTestEmail(
		{ endpoint: 'settings', emailType: type },
		'email_preview'
	);

	const closeModal = () => {
		setIsModalOpen( false );
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
					onRequestClose={ closeModal }
					className="wc-settings-email-preview-send-modal"
				>
					<SendTestEmailForm
						email={ email }
						onEmailChange={ setEmail }
						isSending={ isSending }
						notice={ notice }
						noticeType={ noticeType }
						onSend={ sendEmail }
						onCancel={ closeModal }
					/>
				</Modal>
			) }
		</div>
	);
};
