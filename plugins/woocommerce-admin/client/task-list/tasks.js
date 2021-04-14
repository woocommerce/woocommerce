/**
 * External dependencies
 */

import { __, sprintf } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';
import {
	getHistory,
	getNewPath,
	updateQueryString,
} from '@woocommerce/navigation';
import { Fragment } from '@wordpress/element';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import Appearance from './tasks/appearance';
import { getCategorizedOnboardingProducts } from '../dashboard/utils';
import { Products } from './tasks/products';
import Shipping from './tasks/shipping';
import Tax from './tasks/tax';
import Payments from './tasks/payments';
import {
	installActivateAndConnectWcpay,
	isWCPaySupported,
} from './tasks/payments/methods/wcpay';
import { groupListOfObjectsBy } from '../lib/collections';
import { getLinkTypeAndHref } from '~/store-management-links';

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
	activePlugins,
	countryCode,
	createNotice,
	installAndActivatePlugins,
	installedPlugins,
	isJetpackConnected,
	onboardingStatus,
	profileItems,
	query,
	toggleCartModal,
	onTaskSelect,
} ) {
	const {
		hasPaymentGateway,
		hasPhysicalProducts,
		hasProducts,
		isAppearanceComplete,
		isTaxComplete,
		shippingZonesCount,
		wcPayIsConnected,
	} = {
		hasPaymentGateway: false,
		hasPhysicalProducts: false,
		hasProducts: false,
		isAppearanceComplete: false,
		isTaxComplete: false,
		shippingZonesCount: 0,
		wcPayIsConnected: false,
		...onboardingStatus,
	};

	const groupedProducts = getCategorizedOnboardingProducts(
		profileItems,
		installedPlugins
	);
	const { products, remainingProducts, uniqueItemsList } = groupedProducts;

	const woocommercePaymentsInstalled =
		installedPlugins.indexOf( 'woocommerce-payments' ) !== -1;
	const {
		completed: profilerCompleted,
		product_types: productTypes,
		business_extensions: businessExtensions,
	} = profileItems;

	const woocommercePaymentsSelectedInProfiler = (
		businessExtensions || []
	).includes( 'woocommerce-payments' );

	let purchaseAndInstallText = __(
		'Add paid extensions to your store',
		'woocommerce-admin'
	);

	if ( uniqueItemsList.length === 1 ) {
		const { name: itemName } = uniqueItemsList[ 0 ];
		const purchaseAndInstallFormat = __(
			'Add %s to your store',
			'woocommerce-admin'
		);
		purchaseAndInstallText = sprintf( purchaseAndInstallFormat, itemName );
	}

	const tasks = [
		{
			key: 'store_details',
			title: __( 'Store details', 'woocommerce-admin' ),
			container: null,
			onClick: () => {
				onTaskSelect( 'store_details' );
				getHistory().push( getNewPath( {}, '/setup-wizard', {} ) );
			},
			completed: profilerCompleted,
			visible: true,
			time: __( '4 minutes', 'woocommerce-admin' ),
			type: 'setup',
		},
		{
			key: 'purchase',
			title: purchaseAndInstallText,
			container: null,
			onClick: () => {
				onTaskSelect( 'purchase' );
				return remainingProducts.length ? toggleCartModal() : null;
			},
			visible: products.length,
			completed: products.length && ! remainingProducts.length,
			time: __( '2 minutes', 'woocommerce-admin' ),
			isDismissable: true,
			type: 'setup',
		},
		{
			key: 'products',
			title: __( 'Add products', 'woocommerce-admin' ),
			container: <Products />,
			onClick: () => {
				onTaskSelect( 'products' );
				updateQueryString( { task: 'products' } );
			},
			completed: hasProducts,
			visible: true,
			time: __( '1 minute per product', 'woocommerce-admin' ),
			type: 'setup',
		},
		{
			key: 'woocommerce-payments',
			title: __(
				'Get paid with WooCommerce Payments',
				'woocommerce-admin'
			),
			container: <Fragment />,
			completed: wcPayIsConnected,
			onClick: async ( e ) => {
				if ( e.target.nodeName === 'A' ) {
					// This is a nested link, so don't activate the task.
					return false;
				}

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
					onTaskSelect( 'woocommerce-payments' );
					return installActivateAndConnectWcpay(
						reject,
						createNotice,
						installAndActivatePlugins
					);
				} );
			},
			visible:
				window.wcAdminFeatures.wcpay &&
				woocommercePaymentsSelectedInProfiler &&
				woocommercePaymentsInstalled &&
				isWCPaySupported( countryCode ),
			additionalInfo: __(
				'By setting up, you are agreeing to the <a href="https://wordpress.com/tos/" target="_blank">Terms of Service</a>',
				'woocommerce-admin'
			),
			time: __( '2 minutes', 'woocommerce-admin' ),
			type: 'setup',
		},
		{
			key: 'payments',
			title: __( 'Choose payment methods', 'woocommerce-admin' ),
			container: <Payments />,
			completed: hasPaymentGateway,
			onClick: () => {
				onTaskSelect( 'payments' );
				updateQueryString( { task: 'payments' } );
			},
			visible:
				! woocommercePaymentsInstalled ||
				! woocommercePaymentsSelectedInProfiler ||
				! isWCPaySupported( countryCode ),
			time: __( '2 minutes', 'woocommerce-admin' ),
			type: 'setup',
		},
		{
			key: 'tax',
			title: __( 'Add tax rates', 'woocommerce-admin' ),
			container: <Tax />,
			onClick: () => {
				onTaskSelect( 'tax' );
				updateQueryString( { task: 'tax' } );
			},
			completed: isTaxComplete,
			visible: true,
			time: __( '1 minute', 'woocommerce-admin' ),
			type: 'setup',
		},
		{
			key: 'shipping',
			title: __( 'Set up shipping costs', 'woocommerce-admin' ),
			container: <Shipping />,
			onClick: () => {
				if ( shippingZonesCount > 0 ) {
					window.location = getLinkTypeAndHref( {
						type: 'wc-settings',
						tab: 'shipping',
					} ).href;
				} else {
					onTaskSelect( 'shipping' );
					updateQueryString( { task: 'shipping' } );
				}
			},
			completed: shippingZonesCount > 0,
			visible:
				( productTypes && productTypes.includes( 'physical' ) ) ||
				hasPhysicalProducts,
			time: __( '1 minute', 'woocommerce-admin' ),
			type: 'setup',
		},
		{
			key: 'appearance',
			title: __( 'Personalize your store', 'woocommerce-admin' ),
			container: <Appearance />,
			onClick: () => {
				onTaskSelect( 'appearance' );
				updateQueryString( { task: 'appearance' } );
			},
			completed: isAppearanceComplete,
			visible: true,
			time: __( '2 minutes', 'woocommerce-admin' ),
			type: 'setup',
		},
	];
	return groupListOfObjectsBy(
		applyFilters( 'woocommerce_admin_onboarding_task_list', tasks, query ),
		'type',
		'extension'
	);
}
