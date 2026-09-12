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
	canManageReviews,
	countsEnabled = true,
	canUpdateStock,
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
						// Without the counts endpoint a null count would keep
						// the panel in its loading state forever; zero lets
						// the orders list resolve on its own.
						unreadOrdersCount={
							countsEnabled ? unreadOrdersCount : 0
						}
						orderStatuses={ orderStatuses }
					/>
				</ErrorBoundary>
			),
			title: __( 'Orders', 'woocommerce' ),
		},
		canUpdateStock &&
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
		canManageReviews &&
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
