/**
 * External dependencies
 */
import { Modal } from '@wordpress/components';
import type { ReactNode } from 'react';

/**
 * Internal dependencies
 */
import './style.scss';

type FinanceDrawerProps = {
	title: string;
	onClose: () => void;
	children: ReactNode;
};

/**
 * Side panel built on the accessible Modal component and restyled as a right-anchored drawer.
 */
export const FinanceDrawer = ( {
	title,
	onClose,
	children,
}: FinanceDrawerProps ) => (
	<Modal
		title={ title }
		onRequestClose={ onClose }
		className="woocommerce-finance-drawer"
		overlayClassName="woocommerce-finance-drawer__overlay"
		shouldCloseOnClickOutside
	>
		{ children }
	</Modal>
);
