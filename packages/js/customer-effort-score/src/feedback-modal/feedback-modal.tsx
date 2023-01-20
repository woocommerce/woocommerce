/**
 * External dependencies
 */
import { createElement, useState } from '@wordpress/element';
import PropTypes from 'prop-types';
import { Button, Modal } from '@wordpress/components';
import { Text } from '@woocommerce/experimental';
import { __ } from '@wordpress/i18n';

/**
 * Provides a modal requesting customer feedback.
 *
 * Answers and comments are sent to a callback function.
 *
 * @param {Object}   props                      Component props.
 * @param {Function} props.onSendFeedback       Function to call when the results are sent.
 * @param {string}   props.title                Title displayed in the modal.
 * @param {string}   props.description          Description displayed in the modal.
 * @param {string}   props.isSendButtonDisabled Boolean to enable/disable the send button.
 * @param {string}   props.sendButtonLabel      Label for the send button.
 * @param {string}   props.cancelButtonLabel    Label for the cancel button.
 * @param {Function} props.onCloseModal         Callback for when user closes modal by clicking cancel.
 * @param {Function} props.children             Children to be rendered.
 */
function FeedbackModal( {
	onSendFeedback,
	title,
	description,
	onCloseModal,
	children,
	isSendButtonDisabled,
	sendButtonLabel,
	cancelButtonLabel,
}: {
	onSendFeedback: () => void;
	title: string;
	description?: string;
	onCloseModal?: () => void;
	children?: JSX.Element;
	isSendButtonDisabled?: boolean;
	sendButtonLabel?: string;
	cancelButtonLabel?: string;
} ): JSX.Element | null {
	const [ isOpen, setOpen ] = useState( true );

	const closeModal = () => {
		setOpen( false );
		if ( onCloseModal ) {
			onCloseModal();
		}
	};

	if ( ! isOpen ) {
		return null;
	}

	return (
		<Modal
			className="woocommerce-feedback-modal"
			title={ title }
			onRequestClose={ closeModal }
			shouldCloseOnClickOutside={ false }
		>
			<Text
				variant="body"
				as="p"
				className="woocommerce-feedback-modal__intro"
				size={ 14 }
				lineHeight="20px"
				marginBottom="1.5em"
			>
				{ description }
			</Text>
			{ children }
			<div className="woocommerce-feedback-modal__buttons">
				<Button isTertiary onClick={ closeModal } name="cancel">
					{ cancelButtonLabel }
				</Button>
				<Button
					isPrimary
					onClick={ () => {
						onSendFeedback();
						setOpen( false );
					} }
					name="send"
					disabled={ isSendButtonDisabled }
				>
					{ sendButtonLabel }
				</Button>
			</div>
		</Modal>
	);
}

FeedbackModal.propTypes = {
	onSendFeedback: PropTypes.func.isRequired,
	title: PropTypes.string,
	description: PropTypes.string,
	defaultScore: PropTypes.number,
	onCloseModal: PropTypes.func,
	isSendButtonDisabled: PropTypes.bool,
	sendButtonLabel: PropTypes.string,
	cancelButtonLabel: PropTypes.string,
};

export { FeedbackModal };
