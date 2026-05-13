/**
 * Order status panel — its own Card in the side column above History.
 *
 * Holds the status dropdown; changing it opens the status-change modal
 * (stub Confirm in v1 with email indicator + suppress toggle).
 */

import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Card, CardHeader, CardBody, SelectControl, Button } from '@wordpress/components';
import { useOrder } from '../data/order-context';
import { statusFiresEmail } from '../data/types';
import type { OrderStatusOption } from '../data/types';
import { StatusChangeModal } from './status-change-modal';

interface OrderStatusPanelProps {
	statuses: OrderStatusOption[];
}

export function OrderStatusPanel( { statuses }: OrderStatusPanelProps ) {
	const { order } = useOrder();
	const [ pendingSelection, setPendingSelection ] = useState< string >( '' );
	const [ modalOpen, setModalOpen ] = useState( false );

	// Sync the local selection when the order's current status updates from outside
	// (e.g. on initial load, or after a save).
	useEffect( () => {
		if ( order ) {
			setPendingSelection( order.status );
		}
	}, [ order?.status ] );

	if ( ! order ) {
		return null;
	}

	const currentStatus = order.status;
	const hasPendingChange = pendingSelection !== currentStatus;

	const handleUpdateClick = () => {
		if ( hasPendingChange ) {
			setModalOpen( true );
		}
	};

	const handleCancel = () => {
		setModalOpen( false );
		// Don't reset pendingSelection — keep the user's pick in the dropdown
		// so they can retry from the same state.
	};

	return (
		<Card
			className="wc-react-order-edit__panel wc-react-order-edit__status-panel"
			aria-labelledby="wc-react-order-edit-status-heading"
		>
			<CardHeader className="wc-react-order-edit__panel-header">
				<h2
					id="wc-react-order-edit-status-heading"
					className="wc-react-order-edit__panel-title"
				>
					{ __( 'Status', 'woocommerce' ) }
				</h2>
			</CardHeader>

			<CardBody className="wc-react-order-edit__panel-body">
				<SelectControl
					value={ pendingSelection }
					options={ statuses.map( ( s ) => ( {
						label: s.name,
						value: s.slug,
					} ) ) }
					onChange={ setPendingSelection }
					hideLabelFromVision
					label={ __( 'Order status', 'woocommerce' ) }
					__nextHasNoMarginBottom
					__next40pxDefaultSize
				/>
				<div className="wc-react-order-edit__status-actions">
					<Button
						variant="primary"
						size="compact"
						onClick={ handleUpdateClick }
						disabled={ ! hasPendingChange }
					>
						{ __( 'Update order status', 'woocommerce' ) }
					</Button>
				</div>
			</CardBody>

			{ modalOpen && hasPendingChange && (
				<StatusChangeModal
					currentStatus={ currentStatus }
					newStatus={ pendingSelection }
					newStatusLabel={
						statuses.find( ( s ) => s.slug === pendingSelection )?.name || pendingSelection
					}
					firesEmail={ statusFiresEmail( pendingSelection, currentStatus ) }
					onCancel={ handleCancel }
				/>
			) }
		</Card>
	);
}
