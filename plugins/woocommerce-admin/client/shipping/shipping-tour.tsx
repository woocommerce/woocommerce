/**
 * External dependencies
 */
import { TourKit, TourKitTypes } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';
import { useDispatch, useSelect } from '@wordpress/data';
import { OPTIONS_STORE_NAME, PLUGINS_STORE_NAME } from '@woocommerce/data';

const REVIEWED_DEFAULTS_OPTION =
	'woocommerce_admin_reviewed_default_shipping_zones';

const CREATED_DEFAULTS_OPTION =
	'woocommerce_admin_created_default_shipping_zones';

const useShowShippingTour = () => {
	const {
		hasCreatedDefaultShippingZones,
		hasReviewedDefaultShippingOptions,
		isLoading,
	} = useSelect( ( select ) => {
		const { hasFinishedResolution, getOption } =
			select( OPTIONS_STORE_NAME );

		return {
			isLoading:
				! hasFinishedResolution( 'getOption', [
					CREATED_DEFAULTS_OPTION,
				] ) &&
				! hasFinishedResolution( 'getOption', [
					REVIEWED_DEFAULTS_OPTION,
				] ),
			hasCreatedDefaultShippingZones:
				getOption( CREATED_DEFAULTS_OPTION ) === 'yes',
			hasReviewedDefaultShippingOptions:
				getOption( REVIEWED_DEFAULTS_OPTION ) === 'yes',
		};
	} );

	return {
		isLoading,
		show:
			! isLoading &&
			hasCreatedDefaultShippingZones &&
			! hasReviewedDefaultShippingOptions,
	};
};

export const ShippingTour = () => {
	const { updateOptions } = useDispatch( OPTIONS_STORE_NAME );
	const { show: showTour } = useShowShippingTour();
	const activePlugins = useSelect( ( select ) =>
		select( PLUGINS_STORE_NAME ).getActivePlugins()
	);

	const tourConfig: TourKitTypes.WooConfig = {
		placement: 'auto',
		steps: [
			{
				referenceElements: {
					desktop: 'table.wc-shipping-zones',
				},
				meta: {
					name: 'shipping-zones',
					heading: __( 'Shipping zones', 'woocommerce' ),
					descriptions: {
						desktop: (
							<>
								<span>
									{ __(
										'We added a few shipping zones for you based on your location, but you can manage them at any time.',
										'woocommerce'
									) }
								</span>
								<br />
								<span>
									{ __(
										'A shipping zone is a geography area where a certain set of shippping methods are offered.',
										'woocommerce'
									) }
								</span>
							</>
						),
					},
				},
			},
			{
				referenceElements: {
					desktop: 'table.wc-shipping-zones',
				},
				meta: {
					name: 'shipping-methods',
					heading: __( 'Shipping methods', 'woocommerce' ),
					descriptions: {
						desktop: __(
							'We defaulted to some recommended shippping methods based on your store location, but you can manage them at any time within each shipping zone settings.',
							'woocommerce'
						),
					},
				},
			},
		],
		closeHandler: () => {
			updateOptions( {
				[ REVIEWED_DEFAULTS_OPTION ]: 'yes',
			} );
		},
	};

	const WCS_LINK_SELECTOR = 'a[href*="woocommerce-services-settings"]';
	const isWcsSectionPresent = document.querySelector( WCS_LINK_SELECTOR );

	const SHIPPING_RECOMMENDATIONS_SELECTOR =
		'div.woocommerce-recommended-shipping-extensions';
	const isShippingRecommendationsPresent = ! activePlugins.includes(
		'woocommerce-services'
	);

	if ( isWcsSectionPresent ) {
		tourConfig.steps.push( {
			referenceElements: {
				desktop: WCS_LINK_SELECTOR,
			},
			meta: {
				name: 'woocommerce-shipping',
				heading: __( 'WooCommerce Shipping', 'woocommerce' ),
				descriptions: {
					desktop: __(
						'Print USPS and DHL labels straight from your WooCommerce dashboard and save on shipping thanks to discounted rates. You can manage WooCommerce Shipping in this section.',
						'woocommerce'
					),
				},
			},
		} );
	}

	if ( isShippingRecommendationsPresent ) {
		tourConfig.steps.push( {
			referenceElements: {
				desktop: SHIPPING_RECOMMENDATIONS_SELECTOR,
			},
			meta: {
				name: 'shipping-recommendations',
				heading: __( 'Woocommerce Shipping', 'woocommerce' ),
				descriptions: {
					desktop: __(
						'If you’d like to speed up your process and print your shipping label straight from your WooCommerce dashboard, WooCommerce Shipping may be for you! ',
						'woocommerce'
					),
				},
			},
		} );
	}

	if ( showTour ) {
		return (
			<div>
				<TourKit config={ tourConfig }></TourKit>
			</div>
		);
	}

	return null;
};
