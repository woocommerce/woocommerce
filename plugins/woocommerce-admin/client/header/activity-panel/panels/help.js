/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Text } from '@woocommerce/experimental';
import { withSelect } from '@wordpress/data';
import { Fragment, useEffect } from '@wordpress/element';
import { applyFilters } from '@wordpress/hooks';
import { Icon, chevronRight, page } from '@wordpress/icons';
import { partial } from 'lodash';
import { getSetting } from '@woocommerce/wc-admin-settings';
import { List, Section } from '@woocommerce/components';
import {
	ONBOARDING_STORE_NAME,
	PLUGINS_STORE_NAME,
	SETTINGS_STORE_NAME,
} from '@woocommerce/data';
import { compose } from 'redux';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import ActivityHeader from '../activity-header';
import { getCountryCode } from '../../../dashboard/utils';
import { getPaymentMethods } from '../../../task-list/tasks/payments/methods';

export const SETUP_TASK_HELP_ITEMS_FILTER =
	'woocommerce_admin_setup_task_help_items';

function getHomeItems() {
	return [
		{
			title: __( 'Get Support', 'woocommerce-admin' ),
			link: 'https://woocommerce.com/my-account/create-a-ticket/',
		},
		{
			title: __( 'Home Screen', 'woocommerce-admin' ),
			link: 'https://docs.woocommerce.com/document/home-screen',
		},
		{
			title: __( 'Inbox', 'woocommerce-admin' ),
			link:
				'https://docs.woocommerce.com/document/home-screen/#section-2',
		},
		{
			title: __( 'Stats Overview', 'woocommerce-admin' ),
			link:
				'https://docs.woocommerce.com/document/home-screen/#section-4',
		},
		{
			title: __( 'Store Management', 'woocommerce-admin' ),
			link:
				'https://docs.woocommerce.com/document/home-screen/#section-5',
		},
		{
			title: __( 'Store Setup Checklist', 'woocommerce-admin' ),
			link:
				'https://docs.woocommerce.com/document/woocommerce-setup-wizard/#store-setup-checklist',
		},
	];
}

function getAppearanceItems() {
	return [
		{
			title: __(
				'Showcase your products and tailor your shopping experience using Blocks',
				'woocommerce-admin'
			),
			link:
				'https://docs.woocommerce.com/document/woocommerce-blocks/?utm_source=help_panel',
		},
		{
			title: __(
				'Manage Store Notice, Catalog View and Product Images',
				'woocommerce-admin'
			),
			link:
				'https://docs.woocommerce.com/document/woocommerce-customizer/?utm_source=help_panel',
		},
		{
			title: __(
				'How to choose and change a theme',
				'woocommerce-admin'
			),
			link:
				'https://docs.woocommerce.com/document/choose-change-theme/?utm_source=help_panel',
		},
	];
}

function getPaymentsItems( props ) {
	const { countryCode, onboardingStatus, profileItems } = props;
	const paymentMethods = props.getPaymentMethods( {
		activePlugins: [],
		countryCode,
		onboardingStatus,
		options: {},
		profileItems,
	} );

	const methodIsVisible = ( methodKey ) =>
		Boolean(
			paymentMethods.find( ( method ) => method.key === methodKey )
		);

	const showWCPay = methodIsVisible( 'wcpay' );
	const showStripe = methodIsVisible( 'stripe' );
	const showKlarnaCheckout = methodIsVisible( 'klarna_checkout' );
	const showKlarnaPayments = methodIsVisible( 'klarna_payments' );
	const showPayPal = methodIsVisible( 'paypal' );
	const showSquare = methodIsVisible( 'square' );
	const showPayFast = methodIsVisible( 'payfast' );
	const showEway = methodIsVisible( 'eway' );

	return [
		{
			title: __(
				'Which Payment Option is Right for Me?',
				'woocommerce-admin'
			),
			link:
				'https://docs.woocommerce.com/document/premium-payment-gateway-extensions/?utm_source=help_panel',
		},
		showWCPay && {
			title: __(
				'WooCommerce Payments Start Up Guide',
				'woocommerce-admin'
			),
			link:
				'https://docs.woocommerce.com/document/payments//?utm_source=help_panel',
		},
		showWCPay && {
			title: __( 'WooCommerce Payments FAQs', 'woocommerce-admin' ),
			link:
				'https://docs.woocommerce.com/documentation/woocommerce-payments/woocommerce-payments-faqs/?utm_source=help_panel',
		},
		showStripe && {
			title: __( 'Stripe Setup and Configuration', 'woocommerce-admin' ),
			link:
				'https://docs.woocommerce.com/document/stripe/?utm_source=help_panel',
		},
		showPayPal && {
			title: __(
				'PayPal Checkout Setup and Configuration',
				'woocommerce-admin'
			),
			link:
				'https://docs.woocommerce.com/document/2-0/woocommerce-paypal-payments/#section-3',
		},
		showSquare && {
			title: __( 'Square - Get started', 'woocommerce-admin' ),
			link:
				'https://docs.woocommerce.com/document/woocommerce-square/?utm_source=help_panel',
		},
		showKlarnaCheckout && {
			title: __( 'Klarna - Introduction', 'woocommerce-admin' ),
			link:
				'https://docs.woocommerce.com/document/klarna-checkout/?utm_source=help_panel',
		},
		showKlarnaPayments && {
			title: __( 'Klarna - Introduction', 'woocommerce-admin' ),
			link:
				'https://docs.woocommerce.com/document/klarna-payments/?utm_source=help_panel',
		},
		showPayFast && {
			title: __( 'PayFast Setup and Configuration', 'woocommerce-admin' ),
			link:
				'https://docs.woocommerce.com/document/payfast-payment-gateway/?utm_source=help_panel',
		},
		showEway && {
			title: __( 'eWAY Setup and Configuration', 'woocommerce-admin' ),
			link:
				'https://docs.woocommerce.com/document/eway/?utm_source=help_panel',
		},
		{
			title: __( 'Direct Bank Transfer (BACS)', 'woocommerce-admin' ),
			link:
				'https://docs.woocommerce.com/document/bacs/?utm_source=help_panel',
		},
		{
			title: __( 'Cash on Delivery', 'woocommerce-admin' ),
			link:
				'https://docs.woocommerce.com/document/cash-on-delivery/?utm_source=help_panel',
		},
	].filter( Boolean );
}

function getProductsItems() {
	return [
		{
			title: __( 'Adding and Managing Products', 'woocommerce-admin' ),
			link:
				'https://docs.woocommerce.com/document/managing-products/?utm_source=help_panel',
		},
		{
			title: __(
				'Import products using the CSV Importer and Exporter',
				'woocommerce-admin'
			),
			link:
				'https://docs.woocommerce.com/document/product-csv-importer-exporter/?utm_source=help_panel',
		},
		{
			title: __(
				'Migrate products using Cart2Cart',
				'woocommerce-admin'
			),
			link:
				'https://woocommerce.com/products/cart2cart/?utm_source=help_panel',
		},
		{
			title: __(
				'Learn more about setting up products',
				'woocommerce-admin'
			),
			link:
				'https://docs.woocommerce.com/documentation/plugins/woocommerce/getting-started/setup-products/?utm_source=help_panel',
		},
	];
}

function getShippingItems( { activePlugins, countryCode } ) {
	const showWCS =
		countryCode === 'US' &&
		! activePlugins.includes( 'woocommerce-services' );
	return [
		{
			title: __( 'Setting up Shipping Zones', 'woocommerce-admin' ),
			link:
				'https://docs.woocommerce.com/document/setting-up-shipping-zones/?utm_source=help_panel',
		},
		{
			title: __( 'Core Shipping Options', 'woocommerce-admin' ),
			link:
				'https://docs.woocommerce.com/documentation/plugins/woocommerce/getting-started/shipping/core-shipping-options/?utm_source=help_panel',
		},
		{
			title: __( 'Product Shipping Classes', 'woocommerce-admin' ),
			link:
				'https://docs.woocommerce.com/document/product-shipping-classes/?utm_source=help_panel',
		},
		showWCS && {
			title: __(
				'WooCommerce Shipping setup and configuration',
				'woocommerce-admin'
			),
			link:
				'https://docs.woocommerce.com/document/woocommerce-services/#section-3/?utm_source=help_panel',
		},
		{
			title: __(
				'Learn more about configuring your shipping settings',
				'woocommerce-admin'
			),
			link:
				'https://docs.woocommerce.com/documentation/plugins/woocommerce/getting-started/shipping/?utm_source=help_panel',
		},
	].filter( Boolean );
}

function getTaxItems( props ) {
	const { countryCode } = props;
	const {
		automatedTaxSupportedCountries = [],
		taxJarActivated,
	} = props.getSetting( 'onboarding', {} );

	const showWCS =
		! taxJarActivated && // WCS integration doesn't work with the official TaxJar plugin.
		automatedTaxSupportedCountries.includes( countryCode );

	return [
		{
			title: __( 'Setting up Taxes in WooCommerce', 'woocommerce-admin' ),
			link:
				'https://docs.woocommerce.com/document/setting-up-taxes-in-woocommerce/?utm_source=help_panel',
		},
		showWCS && {
			title: __(
				'Automated Tax calculation using WooCommerce Tax',
				'woocommerce-admin'
			),
			link:
				'https://docs.woocommerce.com/document/woocommerce-services/?utm_source=help_panel#section-10',
		},
	].filter( Boolean );
}

function getItems( props ) {
	const { taskName } = props;

	switch ( taskName ) {
		case 'products':
			return getProductsItems();
		case 'appearance':
			return getAppearanceItems();
		case 'shipping':
			return getShippingItems( props );
		case 'tax':
			return getTaxItems( props );
		case 'payments':
			return getPaymentsItems( props );
		default:
			return getHomeItems();
	}
}

function handleOnItemClick( props, event ) {
	const { taskName } = props;

	// event isn't initially set when triggering link with the keyboard.
	if ( ! event ) {
		return;
	}

	props.recordEvent( 'help_panel_click', {
		task_name: taskName || 'homescreen',
		link: event.currentTarget.href,
	} );
}

function getListItems( props ) {
	const itemsByType = getItems( props );
	const genericDocsLink = {
		title: __( 'WooCommerce Docs', 'woocommerce-admin' ),
		link: 'https://docs.woocommerce.com/?utm_source=help_panel',
	};
	itemsByType.push( genericDocsLink );

	const filteredItems = applyFilters(
		SETUP_TASK_HELP_ITEMS_FILTER,
		itemsByType,
		props.taskName,
		props
	);

	// Filter out items that aren't objects without `title` and `link` properties.
	let validatedItems = Array.isArray( filteredItems )
		? filteredItems.filter(
				( item ) => item instanceof Object && item.title && item.link
		  )
		: [];

	// Default empty array to the generic docs link.
	if ( ! validatedItems.length ) {
		validatedItems = [ genericDocsLink ];
	}

	const onClick = partial( handleOnItemClick, props );

	return validatedItems.map( ( item ) => ( {
		title: (
			<Text
				as="div"
				variant="button"
				weight="600"
				size="14"
				lineHeight="20px"
			>
				{ item.title }
			</Text>
		),
		before: <Icon icon={ page } />,
		after: <Icon icon={ chevronRight } />,
		linkType: 'external',
		target: '_blank',
		href: item.link,
		onClick,
	} ) );
}

export const HelpPanel = ( props ) => {
	const { taskName } = props;
	useEffect( () => {
		props.recordEvent( 'help_panel_open', {
			task_name: taskName || 'homescreen',
		} );
	}, [ taskName ] );

	const listItems = getListItems( props );

	return (
		<Fragment>
			<ActivityHeader
				title={ __( 'Documentation', 'woocommerce-admin' ) }
			/>
			<Section>
				<List
					items={ listItems }
					className="woocommerce-quick-links__list"
				/>
			</Section>
		</Fragment>
	);
};

HelpPanel.defaultProps = {
	getPaymentMethods,
	getSetting,
	recordEvent,
};

export default compose(
	withSelect( ( select ) => {
		const { getProfileItems, getTasksStatus } = select(
			ONBOARDING_STORE_NAME
		);
		const { getSettings } = select( SETTINGS_STORE_NAME );
		const { getActivePlugins } = select( PLUGINS_STORE_NAME );
		const { general: generalSettings = {} } = getSettings( 'general' );
		const activePlugins = getActivePlugins();
		const onboardingStatus = getTasksStatus();
		const profileItems = getProfileItems();

		const countryCode = getCountryCode(
			generalSettings.woocommerce_default_country
		);

		return {
			activePlugins,
			countryCode,
			onboardingStatus,
			profileItems,
		};
	} )
)( HelpPanel );
