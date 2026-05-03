/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Modal } from '@wordpress/components';

/**
 * Internal dependencies
 */
import type { Variation } from '../types';

interface VariationEditModalProps {
	variation: Variation;
	onClose: () => void;
}

export function VariationEditModal( {
	variation,
	onClose,
}: VariationEditModalProps ) {
	return (
		<Modal
			title={ sprintf(
				/* translators: %d: variation ID */
				__( 'Edit variation #%d', 'woocommerce' ),
				variation.id
			) }
			onRequestClose={ onClose }
		>
			{ /* TODO: variation edit form */ }
		</Modal>
	);
}
