/**
 * External dependencies
 */
import { Modal, Button, Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import clsx from 'clsx';

type OverwriteConfirmationModalProps = {
	isOpen: boolean;
	isImporting: boolean;
	onClose: () => void;
	onConfirm: () => void;
	overwrittenItems: string[];
};

export const OverwriteConfirmationModal: React.FC<
	OverwriteConfirmationModalProps
> = ( { isOpen, isImporting, onClose, onConfirm, overwrittenItems } ) => {
	if ( ! isOpen ) return null;
	return (
		<Modal
			title={ __(
				'Your configuration will be overridden',
				'woocommerce'
			) }
			onRequestClose={ onClose }
			className="woocommerce-blueprint-overwrite-modal"
		>
			<p className="woocommerce-blueprint-overwrite-modal__description">
				{ overwrittenItems.length
					? __(
							'Importing the file will overwrite the current configuration for the following items in WooCommerce Settings:',
							'woocommerce'
					  )
					: __(
							'Importing the file will overwrite the current configuration in WooCommerce Settings.',
							'woocommerce'
					  ) }
			</p>

			<ul className="woocommerce-blueprint-overwrite-modal__list">
				{ overwrittenItems.map( ( item ) => (
					<li key={ item }>{ item }</li>
				) ) }
			</ul>

			<div className="woocommerce-blueprint-overwrite-modal__actions">
				<Button
					className="woocommerce-blueprint-overwrite-modal__actions-cancel"
					variant="tertiary"
					onClick={ onClose }
				>
					{ __( 'Cancel', 'woocommerce' ) }
				</Button>
				<Button
					className={ clsx(
						'woocommerce-blueprint-overwrite-modal__actions-import',
						{
							'is-importing': isImporting,
						}
					) }
					variant="primary"
					onClick={ onConfirm }
				>
					{ isImporting ? (
						<Spinner />
					) : (
						__( 'Import', 'woocommerce' )
					) }
				</Button>
			</div>
		</Modal>
	);
};
