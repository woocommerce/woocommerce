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
import { getCountryCode } from '~/dashboard/utils';

export const SETUP_TASK_HELP_ITEMS_FILTER =
	'woocommerce_admin_setup_task_help_items';

function getHomeItems() {
	return [
		{
			title: __( 'Get Support', 'woocommerce' ),
			link: 'https://woocommerce.com/my-account/create-a-ticket/?utm_medium=product',
		},
		{
			title: __( 'Home Screen', 'woocommerce' ),
			link: 'https://woocommerce.com/document/home-screen/?utm_medium=product',
		},
		{
			title: __( 'Inbox', 'woocommerce' ),
			link: 'https://woocommerce.com/document/home-screen/?utm_medium=product#section-4',
		},
		{
			title: __( 'Stats Overview', 'woocommerce' ),
			link: 'https://woocommerce.com/document/home-screen/?utm_medium=product#section-5',
		},
		{
			title: __( 'Store Management', 'woocommerce' ),
			link: 'https://woocommerce.com/document/home-screen/?utm_medium=product#section-10',
		},
		{
			title: __( 'Store Setup Checklist', 'woocommerce' ),
			link: 'https://woocommerce.com/document/woocommerce-setup-wizard?utm_medium=product#store-setup-checklist',
		},
	];
}

function getAppearanceItems() {
	return [
		{
			title: __(
				'Showcase your products and tailor your shopping experience using Blocks',
				'woocommerce'
			),
			link: 'https://woocommerce.com/document/woocommerce-blocks/?utm_source=help_panel&utm_medium=product',
		},
		{
			title: __(
				'Manage Store Notice, Catalog View and Product Images',
				'woocommerce'
			),
			link: 'https://woocommerce.com/document/woocommerce-customizer/?utm_source=help_panel&utm_medium=product',
		},
		{
			title: __( 'How to choose and change a theme', 'woocommerce' ),
			link: 'https://woocommerce.com/document/choose-change-theme/?utm_source=help_panel&utm_medium=product',
		},
	];
}

function getMarketingItems( props ) {
	const { activePlugins } = props;

	return [
		activePlugins.includes( 'mailpoet' ) && {
			title: __( 'Get started with Mailpoet', 'woocommerce' ),
			link: 'https://kb.mailpoet.com/category/114-getting-started',
		},
		activePlugins.includes( 'google-listings-and-ads' ) && {
			title: __( 'Set up Google Listing & Ads', 'woocommerce' ),
			link: 'https://woocommerce.com/document/google-listings-and-ads/?utm_medium=product#get-started',
		},
		activePlugins.includes( 'pinterest-for-woocommerce' ) && {
			title: __( 'Set up Pinterest for WooCommerce', 'woocommerce' ),
			link: 'https://woocommerce.com/products/pinterest-for-woocommerce/',
		},
		activePlugins.includes( 'mailchimp-for-woocommerce' ) && {
			title: __( 'Connect Mailchimp for WooCommerce', 'woocommerce' ),
			link: 'https://mailchimp.com/help/connect-or-disconnect-mailchimp-for-woocommerce/',
		},
		activePlugins.includes( 'creative-mail-by-constant-contact' ) && {
			title: __( 'Set up Creative Mail for WooCommerce', 'woocommerce' ),
			link: 'https://app.creativemail.com/kb/help/WooCommerce',
		},
	].filter( Boolean );
}

function getPaymentGatewaySuggestions( props ) {
	const { paymentGatewaySuggestions } = props;

	return [
		{
			title: __( 'Which Payment Option is Right for Me?', 'woocommerce' ),
			link: 'https://woocommerce.com/document/premium-payment-gateway-extensions/?utm_source=help_panel&utm_medium=product',
		},
		paymentGatewaySuggestions.woocommerce_payments && {
			title: __( 'WooPayments Start Up Guide', 'woocommerce' ),
			link: 'https://woocommerce.com/document/payments/?utm_source=help_panel&utm_medium=product',
		},
		paymentGatewaySuggestions.woocommerce_payments && {
			title: __( 'WooPayments FAQs', 'woocommerce' ),
			link: 'https://woocommerce.com/documentation/woocommerce-payments/woocommerce-payments-faqs/?utm_source=help_panel&utm_medium=product',
		},
		paymentGatewaySuggestions.stripe && {
			title: __( 'Stripe Setup and Configuration', 'woocommerce' ),
			link: 'https://woocommerce.com/document/stripe/?utm_source=help_panel&utm_medium=product',
		},
		paymentGatewaySuggestions[ 'ppcp-gateway' ] && {
			title: __(
				'PayPal Checkout Setup and Configuration',
				'woocommerce'
			),
			link: 'https://woocommerce.com/document/2-0/woocommerce-paypal-payments/?utm_medium=product#section-3',
		},
		paymentGatewaySuggestions.square_credit_card && {
			title: __( 'Square - Get started', 'woocommerce' ),
			link: 'https://woocommerce.com/document/woocommerce-square/?utm_source=help_panel&utm_medium=product',
		},
		paymentGatewaySuggestions.kco && {
			title: __( 'Klarna - Introduction', 'woocommerce' ),
			link: 'https://woocommerce.com/document/klarna-checkout/?utm_source=help_panel&utm_medium=product',
		},
		paymentGatewaySuggestions.klarna_payments && {
			title: __( 'Klarna - Introduction', 'woocommerce' ),
			link: 'https://woocommerce.com/document/klarna-payments/?utm_source=help_panel&utm_medium=product',
		},
		paymentGatewaySuggestions.payfast && {
			title: __( 'Payfast Setup and Configuration', 'woocommerce' ),
			link: 'https://woocommerce.com/document/payfast-payment-gateway/?utm_source=help_panel&utm_medium=product',
		},
		paymentGatewaySuggestions.eway && {
			title: __( 'Eway Setup and Configuration', 'woocommerce' ),
			link: 'https://woocommerce.com/document/eway/?utm_source=help_panel&utm_medium=product',
		},
		{
			title: __( 'Direct Bank Transfer (BACS)', 'woocommerce' ),
			link: 'https://woocommerce.com/document/bacs/?utm_source=help_panel&utm_medium=product',
		},
		{
			title: __( 'Cash on Delivery', 'woocommerce' ),
			link: 'https://woocommerce.com/document/cash-on-delivery/?utm_source=help_panel&utm_medium=product',
		},
	].filter( Boolean );
}

function getProductsItems() {
	return [
		{
			title: __( 'Adding and Managing Products', 'woocommerce' ),
			link: 'https://woocommerce.com/document/managing-products/?utm_source=help_panel&utm_medium=product',
		},
		{
			title: __(
				'Import products using the CSV Importer and Exporter',
				'woocommerce'
			),
			link: 'https://woocommerce.com/document/product-csv-importer-exporter/?utm_source=help_panel&utm_medium=product',
		},
		{
			title: __( 'Migrate products using Cart2Cart', 'woocommerce' ),
			link: 'https://woocommerce.com/products/cart2cart/?utm_source=help_panel&utm_medium=product',
		},
		{
			title: __( 'Learn more about setting up products', 'woocommerce' ),
			link: 'https://woocommerce.com/documentation/plugins/woocommerce/getting-started/setup-products/?utm_source=help_panel&utm_medium=product',
		},
	];
}

function getShippingItems( { activePlugins, countryCode } ) {
	const showWCS =
		countryCode === 'US' &&
		! activePlugins.includes( 'woocommerce-services' );
	return [
		{
			title: __( 'Setting up Shipping Zones', 'woocommerce' ),
			link: 'https://woocommerce.com/document/setting-up-shipping-zones/?utm_source=help_panel&utm_medium=product',
		},
		{
			title: __( 'Core Shipping Options', 'woocommerce' ),
			link: 'https://woocommerce.com/documentation/plugins/woocommerce/getting-started/shipping/core-shipping-options/?utm_source=help_panel&utm_medium=product',
		},
		{
			title: __( 'Product Shipping Classes', 'woocommerce' ),
			link: 'https://woocommerce.com/document/product-shipping-classes/?utm_source=help_panel&utm_medium=product',
		},
		showWCS && {
			title: __(
				'WooCommerce Shipping setup and configuration',
				'woocommerce'
			),
			link: 'https://woocommerce.com/document/woocommerce-shipping-and-tax/?utm_source=help_panel&utm_medium=product#section-3',
		},
		{
			title: __(
				'Learn more about configuring your shipping settings',
				'woocommerce'
			),
			link: 'https://woocommerce.com/document/plugins/woocommerce/getting-started/shipping/?utm_source=help_panel&utm_medium=product',
		},
	].filter( Boolean );
}

function getTaxItems( props ) {
	const { countryCode, taskLists } = props;
	const tasks = taskLists.reduce(
		( acc, taskList ) => [ ...acc, ...taskList.tasks ],
		[]
	);

	const task = tasks.find( ( t ) => t.id === 'tax' );

	if ( ! task ) {
		return;
	}

	const { additionalData } = task;
	const { woocommerceTaxCountries = [], taxJarActivated } = additionalData;

	const showWCS =
		! taxJarActivated && // WCS integration doesn't work with the official TaxJar plugin.
		woocommerceTaxCountries.includes( countryCode );

	return [
		{
			title: __( 'Setting up Taxes in WooCommerce', 'woocommerce' ),
			link: 'https://woocommerce.com/document/setting-up-taxes-in-woocommerce/?utm_source=help_panel&utm_medium=product',
		},
		showWCS && {
			title: __(
				'Automated Tax calculation using WooCommerce Tax',
				'woocommerce'
			),
			link: 'https://woocommerce.com/document/woocommerce-services/?utm_source=help_panel&utm_medium=product#section-10',
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
			return getPaymentGatewaySuggestions( props );
		case 'marketing':
			return getMarketingItems( props );
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
		title: __( 'WooCommerce Docs', 'woocommerce' ),
		link: 'https://woocommerce.com/documentation/?utm_source=help_panel&utm_medium=product',
	};
	itemsByType.push( genericDocsLink );

	/**
	 * Filter an array of help items for the setup task.
	 *
	 * @filter woocommerce_admin_setup_task_help_items
	 * @param {Array.<Object>}                                                    items Array items object based on task.
	 * @param {('products'|'appearance'|'shipping'|'tax'|'payments'|'marketing')} task  url query parameters.
	 * @param {Object}                                                            props React component props.
	 */
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
		linkType: item.linkType ?? 'external',
		target: item.target ?? '_blank',
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
			<ActivityHeader title={ __( 'Documentation', 'woocommerce' ) } />
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
	recordEvent,
};

export default compose(
	withSelect( ( select ) => {
		const { getSettings } = select( SETTINGS_STORE_NAME );
		const { getActivePlugins } = select( PLUGINS_STORE_NAME );
		const { general: generalSettings = {} } = getSettings( 'general' );
		const activePlugins = getActivePlugins();
		const paymentGatewaySuggestions = select( ONBOARDING_STORE_NAME )
			.getPaymentGatewaySuggestions()
			.reduce( ( suggestions, suggestion ) => {
				const { id } = suggestion;
				suggestions[ id ] = true;
				return suggestions;
			}, {} );
		const taskLists = select( ONBOARDING_STORE_NAME ).getTaskLists();

		const countryCode = getCountryCode(
			generalSettings.woocommerce_default_country
		);

		return {
			activePlugins,
			countryCode,
			paymentGatewaySuggestions,
			taskLists,
		};
	} )
)( HelpPanel );
