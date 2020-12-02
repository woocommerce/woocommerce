/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import OrdersPanel from './orders';
import StockPanel from './stock';
import ReviewsPanel from './reviews';

export function getAllPanels( {
	countLowStockProducts,
	countUnreadOrders,
	manageStock,
	orderStatuses,
	totalOrderCount,
	reviewsEnabled,
	countUnapprovedReviews,
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
		manageStock === 'yes' && {
			className: 'woocommerce-homescreen-card',
			count: countLowStockProducts,
			id: 'stock-panel',
			initialOpen: false,
			panel: (
				<StockPanel countLowStockProducts={ countLowStockProducts } />
			),
			title: __( 'Stock', 'woocommerce-admin' ),
		},
		reviewsEnabled === 'yes' && {
			className: 'woocommerce-homescreen-card',
			id: 'reviews-panel',
			count: countUnapprovedReviews,
			initialOpen: false,
			panel: (
				<ReviewsPanel
					hasUnapprovedReviews={ countUnapprovedReviews > 0 }
				/>
			),
			title: __( 'Reviews', 'woocommerce-admin' ),
		},
		// Add another panel row here
	].filter( Boolean );
}
