/**
 * External dependencies
 */
import { NOTES_STORE_NAME } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import { useDispatch } from '@wordpress/data';
import { Button, Modal } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const DismissAllModal = ( { onClose } ) => {
	const { createNotice } = useDispatch( 'core/notices' );

	const { batchUpdateNotes, removeAllNotes } =
		useDispatch( NOTES_STORE_NAME );

	const dismissAllNotes = async () => {
		recordEvent( 'wcadmin_inbox_action_dismissall', {} );
		try {
			const notesRemoved = await removeAllNotes( {
				status: 'unactioned',
			} );
			createNotice(
				'success',
				__( 'All messages dismissed', 'woocommerce' ),
				{
					actions: [
						{
							label: __( 'Undo', 'woocommerce' ),
							onClick: () => {
								batchUpdateNotes(
									notesRemoved.map( ( note ) => note.id ),
									{
										is_deleted: 0,
									}
								);
							},
						},
					],
				}
			);
		} catch ( e ) {
			createNotice(
				'error',
				__( 'Messages could not be dismissed', 'woocommerce' )
			);
			onClose();
		}
	};
	return (
		<>
			<Modal
				title={ __( 'Dismiss all messages', 'woocommerce' ) }
				className="woocommerce-inbox-dismiss-all-modal"
				onRequestClose={ onClose }
			>
				<div className="woocommerce-inbox-dismiss-all-modal__wrapper">
					<div className="woocommerce-usage-modal__message">
						{ __(
							'Are you sure? Inbox messages will be dismissed forever.',
							'woocommerce'
						) }
					</div>
					<div className="woocommerce-usage-modal__actions">
						<Button onClick={ onClose }>
							{ __( 'Cancel', 'woocommerce' ) }
						</Button>
						<Button
							isPrimary
							onClick={ () => {
								dismissAllNotes();
								onClose();
							} }
						>
							{ __( 'Yes, dismiss all', 'woocommerce' ) }
						</Button>
					</div>
				</div>
			</Modal>
		</>
	);
};

export default DismissAllModal;
