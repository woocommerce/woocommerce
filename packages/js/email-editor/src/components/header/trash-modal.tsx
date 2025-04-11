/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, Modal } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import { store as noticesStore } from '@wordpress/notices';

/**
 * Internal dependencies
 */
import { editorCurrentPostType } from '../../store';
import { recordEvent } from '../../events';

export function TrashModal( {
	onClose,
	onRemove,
	postId,
}: {
	onClose: () => void;
	onRemove: () => void;
	postId: number;
} ) {
	const { getLastEntityDeleteError } = useSelect( coreStore );
	const { deleteEntityRecord } = useDispatch( coreStore );
	const { createErrorNotice } = useDispatch( noticesStore );
	const closeCallback = () => {
		onClose();
	};
	const trashCallback = async () => {
		recordEvent( 'trash_modal_move_to_trash_button_clicked' );
		const success = await deleteEntityRecord(
			'postType',
			editorCurrentPostType,
			postId as unknown as string,
			{},
			{ throwOnError: false }
		);
		if ( success ) {
			onRemove();
		} else {
			const lastError = getLastEntityDeleteError(
				'postType',
				editorCurrentPostType,
				postId
			);
			// Already deleted.
			if ( lastError?.code === 410 ) {
				onRemove();
			} else {
				const errorMessage = lastError?.message
					? ( lastError.message as string )
					: __(
							'An error occurred while moving the email to the trash.',
							'woocommerce'
					  );
				recordEvent( 'trash_modal_move_to_trash_error', {
					errorMessage,
				} );
				await createErrorNotice( errorMessage, {
					type: 'snackbar',
					isDismissible: true,
					context: 'email-editor',
				} );
			}
		}
	};
	return (
		<Modal
			className="woocommerce-move-to-trash-modal"
			title={ __( 'Move to trash', 'woocommerce' ) }
			onRequestClose={ () => {
				closeCallback();
				recordEvent( 'trash_modal_closed' );
			} }
			focusOnMount
		>
			<p>
				{ __(
					'Are you sure you want to move this email to trash?',
					'woocommerce'
				) }
			</p>
			<div className="woocommerce-send-preview-modal-footer">
				<Button
					variant="tertiary"
					onClick={ () => {
						closeCallback();
						recordEvent( 'trash_modal_cancel_button_clicked' );
					} }
				>
					{ __( 'Cancel', 'woocommerce' ) }
				</Button>
				<Button variant="primary" onClick={ trashCallback }>
					{ __( 'Move to trash', 'woocommerce' ) }
				</Button>
			</div>
		</Modal>
	);
}

export default TrashModal;
