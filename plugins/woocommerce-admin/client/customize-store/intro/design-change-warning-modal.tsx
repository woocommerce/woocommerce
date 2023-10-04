/**
 * External dependencies
 */
import { Modal } from '@wordpress/components';

export const DesignChangeWarningModal = ( {
	title,
	children,
	isOpen = false,
	onRequestClose = () => {},
	classname = 'woocommerce-customize-store__design-change-warning-modal',
}: {
	title: string;
	children?: JSX.Element[];
	isOpen?: boolean;
	onRequestClose?: () => void;
	classname?: string;
} ) => {
	if ( ! isOpen ) {
		return null;
	}

	return (
		<Modal
			className={ classname }
			title={ title }
			onRequestClose={ onRequestClose }
			shouldCloseOnClickOutside={ false }
		>
			{ children }
		</Modal>
	);
};
