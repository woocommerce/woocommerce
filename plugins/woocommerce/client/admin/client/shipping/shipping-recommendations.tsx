/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { Children, useEffect, useRef } from '@wordpress/element';
import {
	pluginsStore,
	settingsStore,
	onboardingStore,
} from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import { getAdminLink } from '@woocommerce/settings';
import { CardFooter, Text } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { getCountryCode } from '~/dashboard/utils';
import WooCommerceShippingItem from './woocommerce-shipping-item';
import ShipStationItem from './shipstation-item';
import PacklinkItem from './packlink-item';
import { useInstallPlugin } from './shipping-recommendations-utils';
import './shipping-recommendations.scss';
import { TrackedLink } from '~/components/tracked-link/tracked-link';
import { useOptionDismiss } from '~/hooks/use-option-dismiss';
import {
	DismissableList,
	DismissableListHeading,
} from '~/settings-recommendations/dismissable-list';

type ExtensionId = 'woocommerce-shipping' | 'shipstation' | 'packlink';

const COUNTRY_EXTENSIONS_MAP: Record< string, ExtensionId[] > = {
	US: [ 'woocommerce-shipping', 'shipstation' ],
	CA: [ 'shipstation' ],
	FR: [ 'packlink' ],
	ES: [ 'packlink' ],
	IT: [ 'packlink' ],
	DE: [ 'packlink' ],
	GB: [ 'shipstation' ],
	NL: [ 'packlink' ],
	AT: [ 'packlink' ],
	BE: [ 'packlink' ],
	AU: [ 'shipstation' ],
	NZ: [ 'shipstation' ],
	IE: [ 'packlink' ],
	PT: [ 'packlink' ],
};

export const ShippingRecommendationsList = ( {
	children,
}: {
	children: React.ReactNode;
} ) => {
	const { isDismissed, onDismiss } = useOptionDismiss(
		'woocommerce_settings_shipping_recommendations_hidden'
	);

	return (
		<DismissableList
			className="woocommerce-recommended-shipping-extensions"
			isDismissed={ isDismissed }
		>
			<DismissableListHeading onDismiss={ onDismiss }>
				<Text variant="title.small" as="p" size="20" lineHeight="28px">
					{ __( 'Recommended shipping solutions', 'woocommerce' ) }
				</Text>
				<Text
					className="woocommerce-recommended-shipping__header-heading"
					variant="caption"
					as="p"
					size="12"
					lineHeight="16px"
				>
					{ __(
						'We recommend adding one of the following shipping extensions to your store.',
						'woocommerce'
					) }
				</Text>
			</DismissableListHeading>
			<ul className="woocommerce-list">
				{ Children.map( children, ( item ) => (
					<li className="woocommerce-list__item">{ item }</li>
				) ) }
			</ul>
			<CardFooter>
				<TrackedLink
					message={ __(
						// translators: {{Link}} is a placeholder for a html element.
						'Visit {{Link}}the WooCommerce Marketplace{{/Link}} to find more shipping, delivery, and fulfillment solutions.',
						'woocommerce'
					) }
					targetUrl={ getAdminLink(
						'admin.php?page=wc-admin&tab=extensions&path=/extensions&category=shipping-delivery-and-fulfillment'
					) }
					linkType="wc-admin"
					eventName="settings_shipping_recommendation_visit_marketplace_click"
				/>
			</CardFooter>
		</DismissableList>
	);
};

const ShippingRecommendations = () => {
	const [ pluginsBeingSetup, handleInstall, handleActivate ] =
		useInstallPlugin();

	const {
		installedPlugins,
		activePlugins,
		countryCode,
		isSellingDigitalProductsOnly,
	} = useSelect( ( select ) => {
		const settings = select( settingsStore ).getSettings( 'general' );

		const { getInstalledPlugins, getActivePlugins } =
			select( pluginsStore );

		const profileItems =
			select( onboardingStore ).getProfileItems()?.product_types;

		return {
			installedPlugins: getInstalledPlugins(),
			activePlugins: getActivePlugins(),
			countryCode: getCountryCode(
				settings.general?.woocommerce_default_country
			),
			isSellingDigitalProductsOnly:
				profileItems?.length === 1 && profileItems[ 0 ] === 'downloads',
		};
	}, [] );

	const normalizedCountry = countryCode ?? '';

	const extensionsForCountry =
		COUNTRY_EXTENSIONS_MAP[ normalizedCountry ] ?? [];

	// Render every country-mapped recommendation regardless of which partner
	// is already installed: the settings page is meant to surface alternatives
	// the merchant can evaluate and switch to. The onboarding wizard keeps a
	// narrower selection because installing every option there at once is not
	// desired during initial setup.
	const visibleExtensions = isSellingDigitalProductsOnly
		? []
		: extensionsForCountry;

	const visiblePluginSlugs = visibleExtensions
		.map( ( ext ) => EXTENSION_PLUGIN_SLUGS[ ext ] )
		.join( ',' );

	const impressionFired = useRef( false );
	useEffect( () => {
		if ( visibleExtensions.length > 0 && ! impressionFired.current ) {
			recordEvent( 'shipping_partner_impression', {
				context: 'settings',
				country: normalizedCountry,
				plugins: visiblePluginSlugs,
			} );
			impressionFired.current = true;
		}
	}, [ visibleExtensions.length, normalizedCountry, visiblePluginSlugs ] );

	if ( isSellingDigitalProductsOnly ) {
		return <ShippingTour showShippingRecommendationsStep={ false } />;
	}

	if ( visibleExtensions.length === 0 ) {
		return <ShippingTour showShippingRecommendationsStep={ false } />;
	}

	return (
		<div style={ { paddingBottom: 60 } }>
			<ShippingTour showShippingRecommendationsStep={ true } />
			<ShippingRecommendationsList>
				{ visibleExtensions.map( ( ext ) => {
					const isPluginInstalled = installedPlugins.includes(
						EXTENSION_PLUGIN_SLUGS[ ext ]
					);
					const isPluginActive = activePlugins.includes(
						EXTENSION_PLUGIN_SLUGS[ ext ]
					);
					const trackingProps = {
						context: 'settings' as const,
						country: normalizedCountry,
						plugins: visiblePluginSlugs,
					};
					switch ( ext ) {
						case 'woocommerce-shipping':
							return (
								<WooCommerceShippingItem
									key={ ext }
									isPluginInstalled={ isPluginInstalled }
									isPluginActive={ isPluginActive }
									pluginsBeingSetup={ pluginsBeingSetup }
									onInstallClick={ handleInstall }
									onActivateClick={ handleActivate }
									tracking={ trackingProps }
								/>
							);
						case 'shipstation':
							return (
								<ShipStationItem
									key={ ext }
									isPluginInstalled={ isPluginInstalled }
									isPluginActive={ isPluginActive }
									pluginsBeingSetup={ pluginsBeingSetup }
									onInstallClick={ handleInstall }
									onActivateClick={ handleActivate }
									tracking={ trackingProps }
								/>
							);
						case 'packlink':
							return (
								<PacklinkItem
									key={ ext }
									isPluginInstalled={ isPluginInstalled }
									isPluginActive={ isPluginActive }
									pluginsBeingSetup={ pluginsBeingSetup }
									onInstallClick={ handleInstall }
									onActivateClick={ handleActivate }
									tracking={ trackingProps }
								/>
							);
						default:
							return null;
					}
				} ) }
			</ShippingRecommendationsList>
		</div>
	);
};

export default ShippingRecommendations;
