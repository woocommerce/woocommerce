/**
 * External dependencies
 */

import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';
import { get } from 'lodash';

/**
 * WooCommerce dependencies
 */
import { getAdminLink, getSetting } from '@woocommerce/wc-admin-settings';
import { updateQueryString } from '@woocommerce/navigation';

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

export function getAllTasks( {
	profileItems,
	options,
	query,
	toggleCartModal,
	installedPlugins,
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

	const paymentsCompleted = get(
		options,
		[ 'woocommerce_task_list_payments', 'completed' ],
		false
	);
	const paymentsSkipped = get(
		options,
		[ 'woocommerce_task_list_payments', 'skipped' ],
		false
	);

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
			completed: ! remainingProductIds.length,
			time: __( '2 minutes', 'woocommerce-admin' ),
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
			visible: true,
			time: __( '2 minutes', 'woocommerce-admin' ),
		},
	];

	return applyFilters(
		'woocommerce_admin_onboarding_task_list',
		tasks,
		query
	);
}
