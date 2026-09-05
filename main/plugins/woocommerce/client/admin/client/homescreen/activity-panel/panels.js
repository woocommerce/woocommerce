/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { __experimentalErrorBoundary as ErrorBoundary } from '@woocommerce/components';

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
				<ErrorBoundary
					errorMessage={
						<>
							{ __(
								'There was an error getting your orders.',
								'woocommerce'
							) }
							<br />
							{ __( 'Please try again.', 'woocommerce' ) }
						</>
					}
				>
					<OrdersPanel
						unreadOrdersCount={ unreadOrdersCount }
						orderStatuses={ orderStatuses }
					/>
				</ErrorBoundary>
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
					<ErrorBoundary
						errorMessage={
							<>
								{ __(
									'There was an error getting your low stock products.',
									'woocommerce'
								) }
								<br />
								{ __( 'Please try again.', 'woocommerce' ) }
							</>
						}
					>
						<StockPanel
							lowStockProductsCount={ lowStockProductsCount }
						/>
					</ErrorBoundary>
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
					<ErrorBoundary
						errorMessage={
							<>
								{ __(
									'There was an error getting your reviews.',
									'woocommerce'
								) }
								<br />
								{ __( 'Please try again.', 'woocommerce' ) }
							</>
						}
					>
						<ReviewsPanel
							hasUnapprovedReviews={ unapprovedReviewsCount > 0 }
						/>
					</ErrorBoundary>
				),
				title: __( 'Reviews', 'woocommerce' ),
			},
		// Add another panel row here
	].filter( Boolean );
}
