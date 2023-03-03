/**
 * External dependencies
 */
import { TourKit } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';
import { OPTIONS_STORE_NAME } from '@woocommerce/data';
import { useState, useEffect, useRef } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';

const FEEDBACK_TOUR_OPTION = 'woocommerce_ces_product_feedback_shown';
const FEEDBACK_TIMEOUT_MS = 7 * 60 * 1000;

const useShowProductFeedbackTour = (): undefined | boolean => {
	const { hasShownTour } = useSelect( ( select ) => {
		const { getOption } = select( OPTIONS_STORE_NAME );

		return {
			hasShownTour: getOption( FEEDBACK_TOUR_OPTION ) as
				| boolean
				| undefined,
		};
	} );

	return hasShownTour;
};

type ProductFeedbackTourProps = {
	currentTab: string;
};

export const ProductFeedbackTour: React.FC< ProductFeedbackTourProps > = ( {
	currentTab,
} ) => {
	const hasShownTour = useShowProductFeedbackTour();
	const [ isTourVisible, setIsTourVisible ] = useState( false );
	const tourTimeout = useRef< ReturnType< typeof setTimeout > | null >(
		null
	);
	const { updateOptions } = useDispatch( OPTIONS_STORE_NAME );

	const clearTourTimeout = () => {
		clearTimeout( tourTimeout.current as ReturnType< typeof setTimeout > );
		tourTimeout.current = null;
	};

	useEffect( () => {
		if ( hasShownTour !== false ) {
			return;
		}

		tourTimeout.current = setTimeout( () => {
			setIsTourVisible( true );
		}, FEEDBACK_TIMEOUT_MS );

		return () => clearTourTimeout();
	}, [ hasShownTour ] );

	useEffect( () => {
		if ( ! isTourVisible ) {
			return;
		}
		updateOptions( {
			[ FEEDBACK_TOUR_OPTION ]: true,
		} );
	}, [ isTourVisible ] );

	if (
		currentTab === 'feedback' &&
		( isTourVisible || tourTimeout.current )
	) {
		setIsTourVisible( false );
		clearTourTimeout();
	}

	if ( ! isTourVisible ) {
		return null;
	}

	return (
		<TourKit
			config={ {
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
				},
			} }
		/>
	);
};
