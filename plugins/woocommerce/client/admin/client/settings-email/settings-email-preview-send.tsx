/**
 * External dependencies
 */
import { Button, Modal, TextControl } from '@wordpress/components';
import { Icon, check, warning } from '@wordpress/icons';
import apiFetch from '@wordpress/api-fetch';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
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

export const EmailPreviewSend: React.FC< EmailPreviewSendProps > = ( {
	type,
} ) => {
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
		} catch ( e ) {
			const wpError = e as WPError;
			setNotice( wpError.message );
			setNoticeType( 'error' );
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
