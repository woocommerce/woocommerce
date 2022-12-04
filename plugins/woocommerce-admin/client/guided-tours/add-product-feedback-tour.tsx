/**
 * External dependencies
 */
import { TourKit, TourKitTypes } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';
import { OPTIONS_STORE_NAME } from '@woocommerce/data';
import { useState, useEffect, useRef } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */

const FEEDBACK_TOUR_OPTION = 'woocommerce_ces_product_feedback_shown';

const useShowProductFeedbackTour = () => {
	const { tourOptionValue, isLoading } = useSelect( ( select ) => {
		const { hasFinishedResolution, getOption } =
			select( OPTIONS_STORE_NAME );

		return {
			isLoading: ! hasFinishedResolution( 'getOption', [
				FEEDBACK_TOUR_OPTION,
			] ),
			tourOptionValue: getOption( FEEDBACK_TOUR_OPTION ),
		};
	} );

	return {
		isLoading,
		tourOptionValue,
	};
};

type ProductFeedbackTour = {
	currentTab: string;
};

export const ProductFeedbackTour: React.FC< ProductFeedbackTour > = ( {
	currentTab,
} ) => {
	const { isLoading, tourOptionValue } = useShowProductFeedbackTour();
	const [ isTourVisible, setIsTourVisible ] = useState( false );
	const tourTimeout = useRef< ReturnType< typeof setTimeout > | null >(
		null
	);

	const clearTourTimeout = () => {
		clearTimeout( tourTimeout.current as ReturnType< typeof setTimeout > );
	};

	console.debug( 'render', isLoading, tourOptionValue );

	useEffect( () => {
		tourTimeout.current = setTimeout( () => {
			setIsTourVisible( true );
		}, 5 * 1000 );

		return () => clearTourTimeout();
	}, [] );

	useEffect( () => {
		if ( currentTab === 'feedback' ) {
			setIsTourVisible( false );
			clearTourTimeout();
		}
	}, [ currentTab ] );

	const config: TourKitTypes.WooConfig = {
		steps: [
			{
				referenceElements: {
					desktop: '#activity-panel-tab-feedback',
				},
				meta: {
					name: 'product-feedback-tour-1',
					heading: __( '🫣 Feeling stuck?', 'woocommerce' ),
					descriptions: {
						desktop: __(
							'You have been working on this product for a few minutes now. Is there something you’re struggling with? Share your feedback.',
							'woocommerce'
						),
					},
					primaryButton: {
						isHidden: true,
					},
				},
			},
		],
		placement: 'bottom-start',
		options: {
			effects: {
				liveResize: { mutation: true, resize: true },
			},
		},
		closeHandler: ( _steps ) => {
			// recordEvent( 'settings_store_address_tour_dismiss', {
			// 	source,
			// } );
		},
	};

	if ( ! isTourVisible ) {
		return null;
	}
	return <TourKit config={ config }></TourKit>;
};
