/**
 * Status-change confirmation modal.
 *
 * Confirms a status transition. The email indicator + "Don't send email for
 * this change" toggle communicate the side effect. Confirm performs a real
 * PUT `/wc/v3/orders/{id}` with the new `status`; the suppress toggle is
 * decorative in v1 because v3 REST doesn't expose a server-side email-suppress
 * flag — wiring real suppression is Future spec.
 */

import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Modal, Button, CheckboxControl, Notice } from '@wordpress/components';
import { EmailIndicator } from './email-indicator';
import { useOrder } from '../data/order-context';
import { updateOrder, describeError } from '../data/api';

interface StatusChangeModalProps {
	currentStatus: string;
	newStatus: string;
	newStatusLabel: string;
	firesEmail: boolean;
	onCancel: () => void;
}

export function StatusChangeModal( {
	currentStatus,
	newStatus,
	newStatusLabel,
	firesEmail,
	onCancel,
}: StatusChangeModalProps ) {
	const { order, setOrder } = useOrder();
	const [ suppressEmail, setSuppressEmail ] = useState( false );
	const [ saving, setSaving ] = useState( false );
	const [ error, setError ] = useState< string | null >( null );

	const handleConfirm = async () => {
		if ( ! order ) {
			return;
		}
		setSaving( true );
		setError( null );
		try {
			const updated = await updateOrder( order.id, { status: newStatus } );
			setOrder( updated );
			window.dispatchEvent(
				new CustomEvent( 'wc-react-order-edit:snackbar', {
					detail: {
						message: __( 'Order status updated', 'woocommerce' ),
					},
				} )
			);
			onCancel();
		} catch ( err ) {
			setError( describeError( err ) );
		} finally {
			setSaving( false );
		}
	};

	return (
		<Modal
			title={ __( 'Change order status', 'woocommerce' ) }
			onRequestClose={ saving ? () => undefined : onCancel }
			className="wc-react-order-edit__status-modal"
			shouldCloseOnClickOutside={ ! saving }
			shouldCloseOnEsc={ ! saving }
		>
			<div className="wc-react-order-edit__modal-form">
			<p>
				{ /* translators: %s: new status name */ }
				{ __( 'Change status to: ', 'woocommerce' ) }
				<strong>{ newStatusLabel }</strong>
			</p>

			{ firesEmail && (
				<>
					<Notice status="info" isDismissible={ false }>
						<EmailIndicator />
					</Notice>
					<CheckboxControl
						label={ __( "Don't send email for this change", 'woocommerce' ) }
						checked={ suppressEmail }
						onChange={ setSuppressEmail }
						__nextHasNoMarginBottom
					/>
				</>
			) }

			{ error && (
				<Notice status="error" isDismissible={ false }>
					{ error }
				</Notice>
			) }

			<div className="wc-react-order-edit__modal-actions">
				<Button
					variant="tertiary"
					size="compact"
					onClick={ onCancel }
					disabled={ saving }
				>
					{ __( 'Cancel', 'woocommerce' ) }
				</Button>
				<Button
					variant="primary"
					size="compact"
					onClick={ handleConfirm }
					isBusy={ saving }
					disabled={ saving || newStatus === currentStatus }
				>
					{ saving
						? __( 'Saving…', 'woocommerce' )
						: __( 'Confirm', 'woocommerce' ) }
				</Button>
			</div>
			</div>
		</Modal>
	);
}
