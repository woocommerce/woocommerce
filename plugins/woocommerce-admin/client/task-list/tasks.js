/**
 * External dependencies
 */

import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';

/**
 * WooCommerce dependencies
 */
import { getAdminLink, getSetting } from '@woocommerce/wc-admin-settings';
import { updateQueryString } from '@woocommerce/navigation';
import { Fragment } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Appearance from './tasks/appearance';
import Connect from './tasks/connect';
import { getProductIdsForCart } from 'dashboard/utils';
import Products from './tasks/products';
import Shipping from './tasks/shipping';
import Tax from './tasks/tax';
import Payments from './tasks/payments';
import { installActivateAndConnectWcpay } from './tasks/payments/methods';
import { recordEvent } from 'lib/tracks';

export function recordTaskViewEvent(
	taskName,
	isJetpackConnected,
	activePlugins,
	installedPlugins
) {
	recordEvent( 'task_view', {
		task_name: taskName,
		wcs_installed: installedPlugins.includes( 'woocommerce-services' ),
		wcs_active: activePlugins.includes( 'woocommerce-services' ),
		jetpack_installed: installedPlugins.includes( 'jetpack' ),
		jetpack_active: activePlugins.includes( 'jetpack' ),
		jetpack_connected: isJetpackConnected,
	} );
}

export function getAllTasks( {
	profileItems,
	taskListPayments,
	query,
	toggleCartModal,
	activePlugins,
	installedPlugins,
	installAndActivatePlugins,
	createNotice,
	isJetpackConnected,
} ) {
	const {
		hasPhysicalProducts,
		hasProducts,
		isAppearanceComplete,
		isTaxComplete,
		shippingZonesCount,
	} = getSetting( 'onboarding', {
		hasPhysicalProducts: false,
		hasProducts: false,
		isAppearanceComplete: false,
		isTaxComplete: false,
		shippingZonesCount: 0,
	} );

	const productIds = getProductIdsForCart(
		profileItems,
		true,
		installedPlugins
	);
	const remainingProductIds = getProductIdsForCart(
		profileItems,
		false,
		installedPlugins
	);

	const paymentsCompleted = Boolean(
		taskListPayments && taskListPayments.completed
	);
	const paymentsSkipped = Boolean(
		taskListPayments && taskListPayments.skipped
	);

	const woocommercePaymentsInstalled =
		installedPlugins.indexOf( 'woocommerce-payments' ) !== -1;

	const tasks = [
		{
			key: 'store_details',
			title: __( 'Store details', 'woocommerce-admin' ),
			container: null,
			onClick: () => {
				window.location = getAdminLink(
					'admin.php?page=wc-admin&reset_profiler=1'
				);
			},
			completed: profileItems.completed,
			visible: true,
			time: __( '4 minutes', 'woocommerce-admin' ),
		},
		{
			key: 'purchase',
			title: __( 'Purchase & install extensions', 'woocommerce-admin' ),
			container: null,
			onClick: () =>
				remainingProductIds.length ? toggleCartModal() : null,
			visible: productIds.length,
			completed: productIds.length && ! remainingProductIds.length,
			time: __( '2 minutes', 'woocommerce-admin' ),
			isDismissable: true,
		},
		{
			key: 'connect',
			title: __(
				'Connect your store to WooCommerce.com',
				'woocommerce-admin'
			),
			container: <Connect query={ query } />,
			visible:
				profileItems.items_purchased && ! profileItems.wccom_connected,
			completed: profileItems.wccom_connected,
			time: __( '1 minute', 'woocommerce-admin' ),
		},
		{
			key: 'products',
			title: __( 'Add my products', 'woocommerce-admin' ),
			container: <Products />,
			completed: hasProducts,
			visible: true,
			time: __( '1 minute per product', 'woocommerce-admin' ),
		},
		{
			key: 'woocommerce-payments',
			title: __( 'Set up WooCommerce Payments', 'woocommerce-admin' ),
			container: <Fragment />,
			completed: paymentsCompleted || paymentsSkipped,
			onClick: async () => {
				await new Promise( ( resolve, reject ) => {
					// This task doesn't have a view, so the recordEvent call
					// in TaskDashboard.recordTaskView() is never called. So
					// record it here.
					recordTaskViewEvent(
						'wcpay',
						isJetpackConnected,
						activePlugins,
						installedPlugins
					);
					return installActivateAndConnectWcpay(
						resolve,
						reject,
						createNotice,
						installAndActivatePlugins
					);
				} );
			},
			visible:
				window.wcAdminFeatures.wcpay && woocommercePaymentsInstalled,
			time: __( '2 minutes', 'woocommerce-admin' ),
		},
		{
			key: 'appearance',
			title: __( 'Personalize my store', 'woocommerce-admin' ),
			container: <Appearance />,
			completed: isAppearanceComplete,
			visible: true,
			time: __( '2 minutes', 'woocommerce-admin' ),
		},
		{
			key: 'shipping',
			title: __( 'Set up shipping', 'woocommerce-admin' ),
			container: <Shipping />,
			completed: shippingZonesCount > 0,
			visible:
				( profileItems.product_types &&
					profileItems.product_types.includes( 'physical' ) ) ||
				hasPhysicalProducts,
			time: __( '1 minute', 'woocommerce-admin' ),
		},
		{
			key: 'tax',
			title: __( 'Set up tax', 'woocommerce-admin' ),
			container: <Tax />,
			completed: isTaxComplete,
			visible: true,
			time: __( '1 minute', 'woocommerce-admin' ),
		},
		{
			key: 'payments',
			title: __( 'Set up payments', 'woocommerce-admin' ),
			container: <Payments />,
			completed: paymentsCompleted || paymentsSkipped,
			onClick: () => {
				if ( paymentsCompleted || paymentsSkipped ) {
					window.location = getAdminLink(
						'admin.php?page=wc-settings&tab=checkout'
					);
					return;
				}
				updateQueryString( { task: 'payments' } );
			},
			visible: ! woocommercePaymentsInstalled,
			time: __( '2 minutes', 'woocommerce-admin' ),
		},
	];

	return applyFilters(
		'woocommerce_admin_onboarding_task_list',
		tasks,
		query
	);
}
