/**
 * Edit email modal. The customer's email lives on `billing.email` in the v3
 * order schema, so we PUT a partial billing payload to update just that field.
 */

import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Modal, Button, Notice, TextControl } from '@wordpress/components';
import { useOrder } from '../data/order-context';
import { updateOrder, describeError } from '../data/api';

interface EmailEditModalProps {
	onClose: () => void;
}

export function EmailEditModal( { onClose }: EmailEditModalProps ) {
	const { order, setOrder } = useOrder();
	const [ email, setEmail ] = useState( order?.billing.email || '' );
	const [ saving, setSaving ] = useState( false );
	const [ error, setError ] = useState< string | null >( null );

	if ( ! order ) {
		return null;
	}

	const handleSave = async () => {
		setSaving( true );
		setError( null );
		try {
			const updated = await updateOrder( order.id, {
				billing: { ...order.billing, email },
			} );
			setOrder( updated );
			window.dispatchEvent(
				new CustomEvent( 'wc-react-order-edit:snackbar', {
					detail: { message: __( 'Email updated', 'woocommerce' ) },
				} )
			);
			onClose();
		} catch ( err ) {
			setError( describeError( err ) );
		} finally {
			setSaving( false );
		}
	};

	return (
		<Modal
			title={ __( 'Edit email', 'woocommerce' ) }
			onRequestClose={ saving ? () => undefined : onClose }
			className="wc-react-order-edit__email-modal"
			shouldCloseOnClickOutside={ ! saving }
			shouldCloseOnEsc={ ! saving }
		>
			<div className="wc-react-order-edit__modal-form">
				{ error && (
					<Notice status="error" isDismissible={ false }>
						{ error }
					</Notice>
				) }

				<TextControl
					label={ __( 'Email', 'woocommerce' ) }
					type="email"
					value={ email }
					onChange={ setEmail }
					__nextHasNoMarginBottom
					__next40pxDefaultSize
				/>

				<div className="wc-react-order-edit__modal-actions">
					<Button
						variant="tertiary"
						size="compact"
						onClick={ onClose }
						disabled={ saving }
					>
						{ __( 'Cancel', 'woocommerce' ) }
					</Button>
					<Button
						variant="primary"
						size="compact"
						onClick={ handleSave }
						isBusy={ saving }
						disabled={ saving }
					>
						{ saving ? __( 'Saving…', 'woocommerce' ) : __( 'Save', 'woocommerce' ) }
					</Button>
				</div>
			</div>
		</Modal>
	);
}
