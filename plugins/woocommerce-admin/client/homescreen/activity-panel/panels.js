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
	lowStockProductsCount,
	unapprovedReviewsCount,
	unreadOrdersCount,
	manageStock,
	isTaskListHidden,
	orderStatuses,
	publishedProductCount,
	reviewsEnabled,
	totalOrderCount,
} ) {
	if ( ! isTaskListHidden ) {
		return [];
	}

	return [
		totalOrderCount > 0 && {
			className: 'woocommerce-homescreen-card',
			count: unreadOrdersCount,
			collapsible: true,
			id: 'orders-panel',
			initialOpen: false,
			panel: (
				<OrdersPanel
					unreadOrdersCount={ unreadOrdersCount }
					orderStatuses={ orderStatuses }
				/>
			),
			title: __( 'Orders', 'woocommerce' ),
		},
		totalOrderCount > 0 &&
			publishedProductCount > 0 &&
			manageStock === 'yes' && {
				className: 'woocommerce-homescreen-card',
				count: lowStockProductsCount,
				id: 'stock-panel',
				initialOpen: false,
				collapsible: lowStockProductsCount !== 0,
				panel: (
					<StockPanel
						lowStockProductsCount={ lowStockProductsCount }
					/>
				),
				title: __( 'Stock', 'woocommerce' ),
			},
		publishedProductCount > 0 &&
			unapprovedReviewsCount > 0 &&
			reviewsEnabled === 'yes' && {
				className: 'woocommerce-homescreen-card',
				id: 'reviews-panel',
				count: unapprovedReviewsCount,
				initialOpen: false,
				collapsible: unapprovedReviewsCount !== 0,
				panel: (
					<ReviewsPanel
						hasUnapprovedReviews={ unapprovedReviewsCount > 0 }
					/>
				),
				title: __( 'Reviews', 'woocommerce' ),
			},
		// Add another panel row here
	].filter( Boolean );
}
