/**
 * External dependencies
 */
import { FunctionComponent } from 'react';
import { TourKit, TourKitTypes } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';
import { OPTIONS_STORE_NAME } from '@woocommerce/data';
import { useDispatch, useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { EmbeddedBodyProps } from './embedded-body-props';

const REVIEWED_STORE_LOCATION_SETTINGS_OPTION =
	'woocommerce_admin_reviewed_store_location_settings';

const useShowStoreLocationTour = () => {
	const { hasReviewedStoreLocationSettings, isLoading } = useSelect(
		( select ) => {
			const { hasFinishedResolution, getOption } =
				select( OPTIONS_STORE_NAME );

			return {
				isLoading: ! hasFinishedResolution( 'getOption', [
					REVIEWED_STORE_LOCATION_SETTINGS_OPTION,
				] ),
				hasReviewedStoreLocationSettings:
					getOption( REVIEWED_STORE_LOCATION_SETTINGS_OPTION ) ===
					'yes',
			};
		}
	);

	return {
		isLoading,
		show: ! isLoading && ! hasReviewedStoreLocationSettings,
	};
};

const StoreAddressTourOverlay = () => {
	const { isLoading, show } = useShowStoreLocationTour();
	const { updateOptions } = useDispatch( OPTIONS_STORE_NAME );
	const config: TourKitTypes.WooConfig = {
		steps: [
			{
				referenceElements: {
					desktop: '#store_address-description + table.form-table',
				},
				meta: {
					name: 'store-location-tour-step-1',
					heading: 'Add your store location',
					descriptions: {
						desktop: __(
							'Add your store location details such as address and Country to help us configure shipping, taxes, currency and more in a fully automated way.',
							'woocommerce'
						),
					},
					primaryButton: {
						text: __( 'Got it', 'woocommerce' ),
					},
				},
			},
		],
		placement: 'bottom-start',
		options: {
			effects: {
				liveResize: { mutation: true, resize: true },
				spotlight: {
					styles: {
						inset: '0px auto auto -8px', // default inset causes all increase in padding to show up on the right side
						paddingInline: '8px', // Add some padding because the spotlight is right on the edge of the text and that's ugly
					},
					interactivity: {
						enabled: true,
					},
				},
			},
		},
		closeHandler: () => {
			updateOptions( {
				[ REVIEWED_STORE_LOCATION_SETTINGS_OPTION ]: 'yes',
			} );
		},
	};

	if ( isLoading || ! show ) {
		return null;
	}
	return <TourKit config={ config }></TourKit>;
};

export const StoreAddressTour: FunctionComponent<
	EmbeddedBodyProps & { tutorial: boolean }
> = ( { page, tab, tutorial } ) => {
	if ( page !== 'wc-settings' ) {
		return null;
	}

	// tab should be general but general is also the default if not set
	if ( tab !== 'general' && tab !== undefined ) {
		return null;
	}

	// only show tour if tutorial query param is set to true, which is the case when referred from onboarding task list
	if ( ! tutorial ) {
		return null;
	}

	return <StoreAddressTourOverlay />;
};
