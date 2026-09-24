/**
 * External dependencies
 */
import { TourKit, TourKitTypes } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';
import { useUserPreferences } from '@woocommerce/data';
import { createElement, useEffect } from '@wordpress/element';

export const PerformanceMetricsTour = ( {
	hasOpenedMenu,
}: {
	hasOpenedMenu: boolean;
} ) => {
	const {
		updateUserPreferences,
		isRequesting,
		dashboard_performance_tour_shown: hasShownTour,
	} = useUserPreferences();

	const shouldShowTour = ! isRequesting && hasShownTour !== 'yes';

	const dismissTour = () => {
		void updateUserPreferences( {
			dashboard_performance_tour_shown: 'yes',
		} );
	};

	// Opening the menu the tour points at counts as seeing it.
	useEffect( () => {
		if ( hasOpenedMenu && shouldShowTour ) {
			dismissTour();
		}
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ hasOpenedMenu, shouldShowTour ] );

	if ( ! shouldShowTour || hasOpenedMenu ) {
		return null;
	}

	const config: TourKitTypes.WooConfig = {
		steps: [
			{
				referenceElements: {
					desktop:
						'.woocommerce-dashboard__performance-menu .woocommerce-ellipsis-menu__toggle',
				},
				meta: {
					name: 'performance-metrics',
					heading: __(
						'Choose which metrics to display',
						'woocommerce'
					),
					descriptions: {
						desktop: __(
							'Select which key metrics to display in the Performance section.',
							'woocommerce'
						),
					},
					primaryButton: {
						text: __( 'Got it', 'woocommerce' ),
					},
				},
			},
		],
		closeHandler: dismissTour,
	};

	return <TourKit config={ config } />;
};
