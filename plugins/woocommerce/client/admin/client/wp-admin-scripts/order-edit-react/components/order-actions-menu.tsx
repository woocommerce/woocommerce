/**
 * H1-row "More actions" kebab menu.
 *
 * Replaces the legacy `woocommerce-order-actions` meta box. Houses:
 *   - Email customer (resend order details / invoice)
 *   - Regenerate download permissions
 *   - Move to trash
 *
 * v1: all items dispatch a snackbar acknowledging the intent. Wiring each to
 * its real REST endpoint is Future spec.
 */

import { DropdownMenu } from '@wordpress/components';
import { moreVertical } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';

interface OrderActionsMenuProps {
	orderId: number;
}

export function OrderActionsMenu( { orderId }: OrderActionsMenuProps ) {
	const dispatchSnack = ( message: string ) => {
		window.dispatchEvent(
			new CustomEvent( 'wc-react-order-edit:snackbar', {
				detail: { message },
			} )
		);
	};

	const controls = [
		{
			title: __( 'Resend order details email', 'woocommerce' ),
			onClick: () =>
				dispatchSnack(
					/* translators: %d: order ID */
					`v1 stub: resend order details email for #${ orderId } — wire to POST /wc/v3/order-actions/orders/{id}/actions/send_email.`
				),
		},
		{
			title: __( 'Resend invoice to customer', 'woocommerce' ),
			onClick: () =>
				dispatchSnack(
					`v1 stub: resend invoice for #${ orderId }.`
				),
		},
		{
			title: __( 'Regenerate download permissions', 'woocommerce' ),
			onClick: () =>
				dispatchSnack(
					`v1 stub: regenerate download permissions for #${ orderId }.`
				),
		},
		{
			title: __( 'Move to trash', 'woocommerce' ),
			onClick: () =>
				dispatchSnack(
					`v1 stub: move order #${ orderId } to trash — wire to DELETE /wc/v3/orders/${ orderId }?force=false.`
				),
		},
	];

	return (
		<DropdownMenu
			icon={ moreVertical }
			label={ __( 'More actions', 'woocommerce' ) }
			controls={ controls }
		/>
	);
}
