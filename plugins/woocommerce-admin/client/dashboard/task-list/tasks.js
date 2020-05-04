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

	const productIds = getProductIdsForCart( profileItems, true );
	const remainingProductIds = getProductIdsForCart( profileItems );

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
			key: 'purchase',
			title: __( 'Purchase & install extensions', 'woocommerce-admin' ),
			content: __(
				'Purchase, install, and manage your extensions directly from your dashboard',
				'wooocommerce-admin'
			),
			icon: 'extension',
			container: null,
			onClick: () =>
				remainingProductIds.length ? toggleCartModal() : null,
			visible: productIds.length,
			completed: ! remainingProductIds.length,
		},
		{
			key: 'connect',
			title: __(
				'Connect your store to WooCommerce.com',
				'woocommerce-admin'
			),
			content: __(
				'Install and manage your extensions directly from your Dashboard',
				'wooocommerce-admin'
			),
			icon: 'extension',
			container: <Connect query={ query } />,
			visible:
				profileItems.items_purchased && ! profileItems.wccom_connected,
			completed: profileItems.wccom_connected,
		},
		{
			key: 'products',
			title: __( 'Add your first product', 'woocommerce-admin' ),
			content: __(
				'Add products manually, import from a sheet or migrate from another platform',
				'wooocommerce-admin'
			),
			icon: 'add_box',
			container: <Products />,
			completed: hasProducts,
			visible: true,
		},
		{
			key: 'appearance',
			title: __( 'Personalize your store', 'woocommerce-admin' ),
			content: __(
				'Create a custom homepage and upload your logo',
				'wooocommerce-admin'
			),
			icon: 'palette',
			container: <Appearance />,
			completed: isAppearanceComplete,
			visible: true,
		},
		{
			key: 'shipping',
			title: __( 'Set up shipping', 'woocommerce-admin' ),
			content: __(
				'Configure some basic shipping rates to get started',
				'wooocommerce-admin'
			),
			icon: 'local_shipping',
			container: <Shipping />,
			completed: shippingZonesCount > 0,
			visible:
				( profileItems.product_types &&
					profileItems.product_types.includes( 'physical' ) ) ||
				hasPhysicalProducts,
		},
		{
			key: 'tax',
			title: __( 'Set up tax', 'woocommerce-admin' ),
			content: __(
				'Choose how to configure tax rates - manually or automatically',
				'wooocommerce-admin'
			),
			icon: 'account_balance',
			container: <Tax />,
			completed: isTaxComplete,
			visible: true,
		},
		{
			key: 'payments',
			title: __( 'Set up payments', 'woocommerce-admin' ),
			content: __(
				'Select which payment providers you’d like to use and configure them',
				'wooocommerce-admin'
			),
			icon: 'payment',
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
		},
	];

	return applyFilters(
		'woocommerce_admin_onboarding_task_list',
		tasks,
		query
	);
}
