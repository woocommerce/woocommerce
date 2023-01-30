/**
 * External dependencies
 */
import { FunctionComponent } from 'react';
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

const useShowStoreLocationTour = () => {
	const { hasFilledStoreAddress, isLoading } = useSelect( ( select ) => {
		const { hasFinishedResolution, getOption } =
			select( OPTIONS_STORE_NAME );

		return {
			isLoading:
				! hasFinishedResolution( 'getOption', [
					STORE_ADDRESS_SETTINGS_OPTION,
				] ) ||
				! hasFinishedResolution( 'getOption', [
					STORE_CITY_SETTINGS_OPTION,
				] ) ||
				! hasFinishedResolution( 'getOption', [
					STORE_POSTCODE_SETTINGS_OPTION,
				] ),
			hasFilledStoreAddress:
				getOption( STORE_ADDRESS_SETTINGS_OPTION ) !== '' &&
				getOption( STORE_CITY_SETTINGS_OPTION ) !== '' &&
				getOption( STORE_POSTCODE_SETTINGS_OPTION ) !== '',
		};
	} );

	return {
		isLoading,
		show: ! isLoading && ! hasFilledStoreAddress,
	};
};

const isFieldFilled = ( fieldSelector: string ) => {
	const field = document.querySelector< HTMLInputElement >( fieldSelector );
	return !! field && field.value.length > 0;
};

const StoreAddressTourOverlay = () => {
	const { isLoading, show } = useShowStoreLocationTour();
	const [ isDismissed, setIsDismissed ] = useState( false );

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
							'Add your store location details to help us configure shipping, taxes, currency and more in a fully automated way. Once done, click on the "Save" button at the end of the form.',
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
		closeHandler: ( _steps, _currentStepIndex, source ) => {
			const fields_filled = {
				address_1: isFieldFilled( 'input#woocommerce_store_address' ),
				address_2: isFieldFilled( 'input#woocommerce_store_address_2' ),
				city: isFieldFilled( 'input#woocommerce_store_city' ),
				postcode: isFieldFilled( 'input#woocommerce_store_postcode' ),
			};

			recordEvent( 'settings_store_address_tour_dismiss', {
				source, // 'close-btn' | 'done-btn'
				fields_filled,
			} );

			setIsDismissed( true );
		},
	};

	if ( isDismissed || isLoading || ! show ) {
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
