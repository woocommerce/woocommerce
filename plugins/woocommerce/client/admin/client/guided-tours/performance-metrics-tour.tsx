/**
 * External dependencies
 */
import { TourKit, TourKitTypes } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';
import { useUserPreferences } from '@woocommerce/data';
import { createElement, useEffect, useRef } from '@wordpress/element';
import { recordEvent } from '@woocommerce/tracks';

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
	const isTourVisible = shouldShowTour && ! hasOpenedMenu;
	const hasRecordedView = useRef( false );

	const dismissTour = ( source: string ) => {
		recordEvent( 'dash_indicators_tour_dismiss', { source } );
		void updateUserPreferences( {
			dashboard_performance_tour_shown: 'yes',
		} );
	};

	useEffect( () => {
		if ( isTourVisible && ! hasRecordedView.current ) {
			hasRecordedView.current = true;
			recordEvent( 'dash_indicators_tour_view' );
		}
	}, [ isTourVisible ] );

	// Opening the menu the tour points at counts as seeing it.
	useEffect( () => {
		if ( hasOpenedMenu && shouldShowTour ) {
			dismissTour( 'menu' );
		}
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ hasOpenedMenu, shouldShowTour ] );

	if ( ! isTourVisible ) {
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
							'Some are hidden by default. Add or remove them from this menu.',
							'woocommerce'
						),
					},
					primaryButton: {
						text: __( 'Got it', 'woocommerce' ),
					},
				},
			},
		],
		closeHandler: ( steps, currentStepIndex, source ) =>
			dismissTour( source ),
		options: {
			// Setting effects replaces the TourKit defaults, so they are repeated
			// here alongside autoScroll. Merchants can move the Performance
			// section below the fold, and the spotlight would then lock a page
			// whose tour sits off-screen.
			effects: {
				spotlight: {
					interactivity: {
						enabled: true,
						rootElementSelector: '#wpwrap',
					},
				},
				arrowIndicator: true,
				autoScroll: {
					behavior: 'auto',
					block: 'center',
				},
				liveResize: {
					mutation: true,
					resize: true,
					rootElementSelector: '#wpwrap',
				},
			},
		},
	};

	return <TourKit config={ config } />;
};
