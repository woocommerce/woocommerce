/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Modal } from '@wordpress/components';

/**
 * Internal dependencies
 */
import type { VariationEntityRecord } from './types';

type VariationEditModalProps = {
	variation: VariationEntityRecord;
	onClose: () => void;
};

export function VariationEditModal( {
	variation,
	onClose,
}: VariationEditModalProps ) {
	return (
		// @ts-expect-error missing types.
		<Modal
			title={ sprintf(
				/* translators: %d: variation ID. */
				__( 'Edit variation #%d', 'woocommerce' ),
				variation.id
			) }
			onRequestClose={ onClose }
		>
			{ /* TODO: variation edit form */ }
		</Modal>
	);
}
