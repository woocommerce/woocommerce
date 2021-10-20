/**
 * External dependencies
 */
import { Button, Modal } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const DissmissModal = ( {
	showDismissModal,
	setShowDismissModal,
	hideTasks,
} ) => {
	const closeModal = () => setShowDismissModal( false );
	const title = __( 'Hide store setup tasks', 'woocommerce-admin' );
	const message = __(
		'Are you sure? These tasks are required for all stores.',
		'woocommerce-admin'
	);
	const dismissActionText = __( 'Cancel', 'woocommerce-admin' );
	const acceptActionText = __(
		'Yes, hide store setup tasks',
		'woocommerce-admin'
	);
	return (
		<>
			{ showDismissModal && (
				<Modal
					title={ title }
					className="woocommerce-task-dismiss-modal"
					onRequestClose={ closeModal }
				>
					<div className="woocommerce-task-dismiss-modal__wrapper">
						<div className="woocommerce-usage-modal__message">
							{ message }
						</div>
						<div className="woocommerce-usage-modal__actions">
							<Button
								onClick={ () => setShowDismissModal( false ) }
							>
								{ dismissActionText }
							</Button>
							<Button
								isPrimary
								onClick={ () => {
									hideTasks( 'remove_card' );
									setShowDismissModal( false );
								} }
							>
								{ acceptActionText }
							</Button>
						</div>
					</div>
				</Modal>
			) }
		</>
	);
};

export default DissmissModal;
