/**
 * External dependencies
 */
import { TourKit, TourKitTypes } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';
import { optionsStore } from '@woocommerce/data';
import { createElement } from '@wordpress/element';
import { useSelect } from '@wordpress/data';

export const PERFORMANCE_TOUR_OPTION =
	'woocommerce_analytics_performance_tour_shown';

export const PerformanceMetricsTour = ( {
	onDismiss,
}: {
	onDismiss: () => void;
} ) => {
	const { shouldShowTour, isResolving } = useSelect( ( select ) => {
		const { getOption, hasFinishedResolution } = select( optionsStore );

		return {
			shouldShowTour: getOption( PERFORMANCE_TOUR_OPTION ) !== 'yes',
			isResolving: ! hasFinishedResolution( 'getOption', [
				PERFORMANCE_TOUR_OPTION,
			] ),
		};
	}, [] );

	if ( ! shouldShowTour || isResolving ) {
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
		closeHandler: onDismiss,
	};

	return <TourKit config={ config } />;
};
