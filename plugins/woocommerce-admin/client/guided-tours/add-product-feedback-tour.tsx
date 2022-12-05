/**
 * External dependencies
 */
import { TourKit, TourKitTypes } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';
import { OPTIONS_STORE_NAME } from '@woocommerce/data';
import { useState, useEffect, useRef } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */

const FEEDBACK_TOUR_OPTION = 'woocommerce_ces_product_feedback_shown';

const useShowProductFeedbackTour = (): undefined | boolean => {
	const { tourOptionValue } = useSelect( ( select ) => {
		const { getOption } = select( OPTIONS_STORE_NAME );

		return {
			tourOptionValue: getOption( FEEDBACK_TOUR_OPTION ) as
				| boolean
				| undefined,
		};
	} );

	return tourOptionValue;
};

type ProductFeedbackTour = {
	currentTab: string;
};

export const ProductFeedbackTour: React.FC< ProductFeedbackTour > = ( {
	currentTab,
} ) => {
	const tourOptionValue = useShowProductFeedbackTour();
	const [ isTourVisible, setIsTourVisible ] = useState( false );
	const tourTimeout = useRef< ReturnType< typeof setTimeout > | null >(
		null
	);
	const { updateOptions } = useDispatch( OPTIONS_STORE_NAME );

	const clearTourTimeout = () => {
		clearTimeout( tourTimeout.current as ReturnType< typeof setTimeout > );
	};

	useEffect( () => {
		if ( tourOptionValue !== false ) {
			return;
		}

		tourTimeout.current = setTimeout( () => {
			setIsTourVisible( true );
		}, 5 * 1000 );

		return () => clearTourTimeout();
	}, [ tourOptionValue ] );

	useEffect( () => {
		if ( currentTab === 'feedback' ) {
			setIsTourVisible( false );
			clearTourTimeout();
		}
	}, [ currentTab ] );

	useEffect( () => {
		if ( ! isTourVisible ) {
			return;
		}
		updateOptions( {
			[ FEEDBACK_TOUR_OPTION ]: true,
		} );
	}, [ isTourVisible ] );

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
							"You have been working on this product for a few minutes now. Is there something you're struggling with? Share your feedback.",
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
		closeHandler: () => {
			setIsTourVisible( false );
			// Add tracks?
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
