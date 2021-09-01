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
import { Marketing, getMarketingExtensionLists } from './tasks/Marketing';
import { Products } from './tasks/products';
import Shipping from './tasks/shipping';
import Tax from './tasks/tax';
import { PaymentGatewaySuggestions } from './tasks/PaymentGatewaySuggestions';
import {
	installActivateAndConnectWcpay,
	isWCPaySupported,
} from './tasks/PaymentGatewaySuggestions/components/WCPay';
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
	freeExtensions,
	installAndActivatePlugins,
	installedPlugins,
	isJetpackConnected,
	onboardingStatus,
	profileItems,
	query,
	toggleCartModal,
	onTaskSelect,
	hasCompleteAddress,
	trackedCompletedActions,
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
	const woocommerceServicesActive =
		activePlugins.indexOf( 'woocommerce-services' ) !== -1;
	const {
		completed: profilerCompleted,
		product_types: productTypes,
		business_extensions: businessExtensions,
	} = profileItems;

	const woocommercePaymentsSelectedInProfiler = (
		businessExtensions || []
	).includes( 'woocommerce-payments' );

	let purchaseAndInstallTitle = __(
		'Add paid extensions to my store',
		'woocommerce-admin'
	);
	let purchaseAndInstallContent;

	if ( uniqueItemsList.length === 1 ) {
		const { name: itemName } = uniqueItemsList[ 0 ];
		const purchaseAndInstallFormat = __(
			'Add %s to my store',
			'woocommerce-admin'
		);
		purchaseAndInstallTitle = sprintf( purchaseAndInstallFormat, itemName );
		purchaseAndInstallContent = products.find(
			( { label } ) => label === itemName
		)?.description;
	} else {
		const uniqueProductNames = uniqueItemsList.map( ( { name } ) => name );
		const lastProduct = uniqueProductNames.pop();
		let firstProducts = uniqueProductNames.join( ', ' );
		if ( uniqueProductNames.length > 1 ) {
			firstProducts += ',';
		}
		/* translators: %1$s: list of product names comma separated, %2%s the last product name */
		purchaseAndInstallContent = sprintf(
			__(
				'Good choice! You chose to add %1$s and %2$s to your store.',
				'woocommerce-admin'
			),
			firstProducts,
			lastProduct
		);
	}

	const {
		automatedTaxSupportedCountries = [],
		taxJarActivated,
	} = onboardingStatus;

	const isTaxJarSupported =
		! taxJarActivated && // WCS integration doesn't work with the official TaxJar plugin.
		automatedTaxSupportedCountries.includes( countryCode );

	const canUseAutomatedTaxes =
		hasCompleteAddress && woocommerceServicesActive && isTaxJarSupported;

	let taxAction = __( "Let's go", 'woocommerce-admin' );
	let taxContent = __(
		'Set your store location and configure tax rate settings.',
		'woocommerce-admin'
	);

	if ( canUseAutomatedTaxes ) {
		taxAction = __( 'Yes please', 'woocommerce-admin' );
		taxContent = __(
			'Good news! WooCommerce Services and Jetpack can automate your sales tax calculations for you.',
			'woocommerce-admin'
		);
	}

	const [
		installedMarketingExtensions,
		marketingExtensionsLists,
	] = getMarketingExtensionLists(
		freeExtensions,
		activePlugins,
		installedPlugins
	);

	const tasks = [
		{
			key: 'store_details',
			title: __( 'Store details', 'woocommerce-admin' ),
			content: __(
				'Your store address is required to set the origin country for shipping, currencies, and payment options.',
				'woocommerce-admin'
			),
			container: null,
			action: __( "Let's go", 'woocommerce-admin' ),
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
			title: purchaseAndInstallTitle,
			content: purchaseAndInstallContent,
			container: null,
			action: __( 'Purchase & install now', 'woocommerce-admin' ),
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
			title: __( 'Add my products', 'woocommerce-admin' ),
			content: __(
				'Start by adding the first product to your store. You can add your products manually, via CSV, or import them from another service.',
				'woocommerce-admin'
			),
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
			content: __(
				"You're only one step away from getting paid. Verify your business details to start managing transactions with WooCommerce Payments.",
				'woocommerce-admin'
			),
			action: __( 'Finish setup', 'woocommmerce-admin' ),
			expanded: true,
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
			title: __( 'Set up payments', 'woocommerce-admin' ),
			content: __(
				'Choose payment providers and enable payment methods at checkout.',
				'woocommerce-admin'
			),
			container: <PaymentGatewaySuggestions query={ query } />,
			completed: hasPaymentGateway,
			onClick: () => {
				onTaskSelect( 'payments' );
				updateQueryString( { task: 'payments' } );
			},
			visible:
				window.wcAdminFeatures[ 'payment-gateway-suggestions' ] &&
				( ! woocommercePaymentsInstalled ||
					! woocommercePaymentsSelectedInProfiler ||
					! isWCPaySupported( countryCode ) ),
			time: __( '2 minutes', 'woocommerce-admin' ),
			type: 'setup',
		},
		{
			key: 'tax',
			title: __( 'Set up tax', 'woocommerce-admin' ),
			content: taxContent,
			container: <Tax />,
			action: taxAction,
			onClick: ( e, args = {} ) => {
				// The expanded item CTA allows us to enable
				// automated taxes for eligible stores.
				// Note: this will be initially part of an A/B test.
				const { isExpanded } = args;
				onTaskSelect( 'tax' );
				updateQueryString( {
					task: 'tax',
					auto: canUseAutomatedTaxes && isExpanded,
				} );
			},
			completed: isTaxComplete,
			visible: true,
			time: __( '1 minute', 'woocommerce-admin' ),
			type: 'setup',
		},
		{
			key: 'shipping',
			title: __( 'Set up shipping', 'woocommerce-admin' ),
			content: __(
				"Set your store location and where you'll ship to.",
				'woocommerce-admin'
			),
			container: <Shipping />,
			action: __( "Let's go", 'woocommerce-admin' ),
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
			key: 'marketing',
			title: __( 'Set up marketing tools', 'woocommerce-admin' ),
			content: __(
				'Add recommended marketing tools to reach new customers and grow your business',
				'woocommerce-admin'
			),
			container: (
				<Marketing
					trackedCompletedActions={ trackedCompletedActions }
				/>
			),
			onClick: () => {
				onTaskSelect( 'marketing' );
				updateQueryString( { task: 'marketing' } );
			},
			completed:
				( !! installedMarketingExtensions.length &&
					trackedCompletedActions.includes( 'marketing' ) ) ||
				! marketingExtensionsLists.length,
			visible:
				window.wcAdminFeatures &&
				window.wcAdminFeatures[ 'remote-free-extensions' ] &&
				( !! marketingExtensionsLists.length ||
					!! installedMarketingExtensions.length ),
			time: __( '1 minute', 'woocommerce-admin' ),
			type: 'setup',
		},
		{
			key: 'appearance',
			title: __( 'Personalize my store', 'woocommerce-admin' ),
			content: __(
				'Add your logo, create a homepage, and start designing your store.',
				'woocommerce-admin'
			),
			container: <Appearance />,
			action: __( "Let's go", 'woocommerce-admin' ),
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
	const filteredTasks = applyFilters(
		'woocommerce_admin_onboarding_task_list',
		tasks,
		query
	);
	for ( const task of filteredTasks ) {
		task.level = task.level ? parseInt( task.level, 10 ) : 3;
	}
	return groupListOfObjectsBy( filteredTasks, 'type', 'extension' );
}

export function taskSort( a, b ) {
	if ( a.completed || b.completed ) {
		return a.completed ? 1 : -1;
	}
	// Three is the lowest level.
	const aLevel = a.level || 3;
	const bLevel = b.level || 3;
	if ( aLevel === bLevel ) {
		return 0;
	}
	return aLevel > bLevel ? 1 : -1;
}
