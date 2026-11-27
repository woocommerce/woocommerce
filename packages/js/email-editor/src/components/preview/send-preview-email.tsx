/**
 * External dependencies
 */
import { Button, Modal, TextControl } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { check, Icon } from '@wordpress/icons';
import { __, sprintf } from '@wordpress/i18n';
import {
	useEffect,
	useRef,
	createInterpolateElement,
	memo,
	isValidElement,
} from '@wordpress/element';
import { ENTER } from '@wordpress/keycodes';
import { isEmail } from '@wordpress/url';
import { applyFilters } from '@wordpress/hooks';
import type { ReactNode } from 'react';

/**
 * Internal dependencies
 */
import { SendingPreviewStatus, storeName } from '../../store';
import { recordEvent, recordEventOnce } from '../../events';

function RawSendPreviewEmail() {
	const sendToRef = useRef( null );

	const {
		requestSendingNewsletterPreview,
		togglePreviewModal,
		updateSendPreviewEmail,
	} = useDispatch( storeName );

	const {
		toEmail: previewToEmail,
		isSendingPreviewEmail,
		sendingPreviewStatus,
		isModalOpened,
		errorMessage,
		postType,
	} = useSelect(
		( select ) => ( {
			...select( storeName ).getPreviewState(),
			postType: select( storeName ).getEmailPostType(),
		} ),
		[]
	);

	const handleSendPreviewEmail = () => {
		void requestSendingNewsletterPreview( previewToEmail );
	};

	const closeCallback = () => {
		recordEvent( 'send_preview_email_modal_closed' );
		void togglePreviewModal( false );
	};

	// We use this effect to focus on the input field when the modal is opened
	useEffect( () => {
		if ( isModalOpened ) {
			sendToRef.current?.focus();
			recordEvent( 'send_preview_email_modal_opened' );
		}
	}, [ isModalOpened ] );

	if ( ! isModalOpened ) {
		return null;
	}

	// Read on every render, so callbacks added after the editor loaded still apply.
	const filteredLink = applyFilters(
		'woocommerce_email_editor_check_sending_method_configuration_link',
		'',
		postType
	);
	const sendingMethodConfigurationLink =
		typeof filteredLink === 'string' ? filteredLink : '';
	const filteredHelpItems = applyFilters(
		'woocommerce_email_editor_send_preview_help_items',
		[],
		postType
	);
	const helpItems = Array.isArray( filteredHelpItems )
		? ( filteredHelpItems as unknown[] ).filter(
				( item ): item is ReactNode =>
					typeof item === 'string' || isValidElement( item )
			)
		: [];

	return (
		<Modal
			className="woocommerce-send-preview-email"
			title={ __( 'Send a test email', __i18n_text_domain__ ) }
			onRequestClose={ closeCallback }
			focusOnMount={ false }
		>
			{ sendingPreviewStatus === SendingPreviewStatus.ERROR ? (
				<div className="woocommerce-send-preview-modal-notice-error">
					<p>
						{ __(
							'Sorry, we were unable to send this email.',
							__i18n_text_domain__
						) }
					</p>

					<strong>
						{ errorMessage &&
							sprintf(
								// translators: %s is an error message.
								__( 'Error: %s', __i18n_text_domain__ ),
								errorMessage
							) }
					</strong>

					{ ( sendingMethodConfigurationLink ||
						helpItems.length > 0 ) && (
						<ul>
							{ sendingMethodConfigurationLink && (
								<li>
									{ createInterpolateElement(
										__(
											'Please check your <link>sending method configuration</link> with your hosting provider.',
											__i18n_text_domain__
										),
										{
											link: (
												// eslint-disable-next-line jsx-a11y/anchor-has-content
												<a
													href={
														sendingMethodConfigurationLink
													}
													target="_blank"
													rel="noopener noreferrer"
													onClick={ () =>
														recordEvent(
															'send_preview_email_modal_check_sending_method_configuration_link_clicked'
														)
													}
												/>
											),
										}
									) }
								</li>
							) }
							{ helpItems.map( ( item, index ) => (
								<li key={ index }>{ item }</li>
							) ) }
						</ul>
					) }
				</div>
			) : null }
			<p>
				{ __(
					'Send yourself a test email to test how your email would look like in different email apps.',
					__i18n_text_domain__
				) }
			</p>
			<TextControl
				label={ __( 'Send to', __i18n_text_domain__ ) }
				onChange={ ( email ) => {
					void updateSendPreviewEmail( email );
					recordEventOnce(
						'send_preview_email_modal_send_to_field_updated'
					);
				} }
				onKeyDown={ ( event ) => {
					const { keyCode } = event;
					if ( keyCode === ENTER ) {
						event.preventDefault();
						handleSendPreviewEmail();
						recordEvent(
							'send_preview_email_modal_send_to_field_key_code_enter'
						);
					}
				} }
				className="woocommerce-send-preview-email__send-to-field"
				value={ previewToEmail }
				type="email"
				ref={ sendToRef }
				required
				__next40pxDefaultSize
				__nextHasNoMarginBottom
			/>
			{ sendingPreviewStatus === SendingPreviewStatus.SUCCESS ? (
				<p className="woocommerce-send-preview-modal-notice-success">
					<Icon icon={ check } style={ { fill: '#4AB866' } } />
					{ __(
						'Test email sent successfully!',
						__i18n_text_domain__
					) }
				</p>
			) : null }
			<div className="woocommerce-send-preview-modal-footer">
				<Button
					variant="tertiary"
					onClick={ () => {
						recordEvent(
							'send_preview_email_modal_close_button_clicked'
						);
						closeCallback();
					} }
				>
					{ __( 'Cancel', __i18n_text_domain__ ) }
				</Button>
				<Button
					variant="primary"
					onClick={ () => {
						handleSendPreviewEmail();
						recordEvent(
							'send_preview_email_modal_send_test_email_button_clicked'
						);
					} }
					disabled={
						isSendingPreviewEmail || ! isEmail( previewToEmail )
					}
				>
					{ isSendingPreviewEmail
						? __( 'Sending…', __i18n_text_domain__ )
						: __( 'Send test email', __i18n_text_domain__ ) }
				</Button>
			</div>
		</Modal>
	);
}

export const SendPreviewEmail = memo( RawSendPreviewEmail );
