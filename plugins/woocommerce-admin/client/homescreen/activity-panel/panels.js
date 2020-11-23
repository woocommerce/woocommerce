/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import OrdersPanel from './orders';

export function getAllPanels( {
	countUnreadOrders,
	orderStatuses,
	totalOrderCount,
} ) {
	return [
		totalOrderCount > 0 && {
			className: 'woocommerce-homescreen-card',
			count: countUnreadOrders,
			id: 'orders-panel',
			initialOpen: true,
			panel: (
				<OrdersPanel
					countUnreadOrders={ countUnreadOrders }
					orderStatuses={ orderStatuses }
				/>
			),
			title: __( 'Orders', 'woocommerce-admin' ),
		},
		// Add another panel row here
	].filter( Boolean );
}
