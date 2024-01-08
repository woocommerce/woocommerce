/**
 * External dependencies
 */
import { Modal } from '@wordpress/components';

export default function withModal(
	comp: JSX.Element,
	title: string,
	onClose: () => void
): JSX.Element {
	return (
		<Modal
			title={ title }
			onRequestClose={ onClose }
			focusOnMount={ true }
			className="woocommerce-marketplace__header-account-modal has-size-medium"
			style={ { borderRadius: 4 } }
			overlayClassName="woocommerce-marketplace__header-account-modal-overlay"
		>
			{ comp }
		</Modal>
	);
}
