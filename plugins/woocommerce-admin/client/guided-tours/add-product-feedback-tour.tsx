/**
 * External dependencies
 */
import { TourKit, TourKitTypes } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';
import { OPTIONS_STORE_NAME } from '@woocommerce/data';
import { useState } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { EmbeddedBodyProps } from '../embedded-body-layout/embedded-body-props';

const STORE_ADDRESS_SETTINGS_OPTION = 'woocommerce_store_address';
const STORE_CITY_SETTINGS_OPTION = 'woocommerce_store_city';
const STORE_POSTCODE_SETTINGS_OPTION = 'woocommerce_store_postcode';

// const useShowStoreLocationTour = () => {
// 	const { hasFilledStoreAddress, isLoading } = useSelect( ( select ) => {
// 		const { hasFinishedResolution, getOption } =
// 			select( OPTIONS_STORE_NAME );

// 		return {
// 			isLoading:
// 				! hasFinishedResolution( 'getOption', [
// 					STORE_ADDRESS_SETTINGS_OPTION,
// 				] ) ||
// 				! hasFinishedResolution( 'getOption', [
// 					STORE_CITY_SETTINGS_OPTION,
// 				] ) ||
// 				! hasFinishedResolution( 'getOption', [
// 					STORE_POSTCODE_SETTINGS_OPTION,
// 				] ),
// 			hasFilledStoreAddress:
// 				getOption( STORE_ADDRESS_SETTINGS_OPTION ) !== '' &&
// 				getOption( STORE_CITY_SETTINGS_OPTION ) !== '' &&
// 				getOption( STORE_POSTCODE_SETTINGS_OPTION ) !== '',
// 		};
// 	} );

// 	return {
// 		isLoading,
// 		show: ! isLoading && ! hasFilledStoreAddress,
// 	};
// };

export const ProductFeedbackTour: React.FC = () => {
	const { isLoading, show } = { isLoading: false, show: true }; //useShowStoreLocationTour();
	const [ isDismissed, setIsDismissed ] = useState( false );

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

			setIsDismissed( true );
		},
	};

	if ( isDismissed || isLoading || ! show ) {
		return null;
	}
	return <TourKit config={ config }></TourKit>;
};
