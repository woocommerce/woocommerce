/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { CoreProfilerStateMachineContext } from '../index';
import { BusinessLocationEvent } from '../events';
import { CountryStateOption } from '../services/country';
import { Heading } from '../components/heading/heading';
import { Navigation } from '../components/navigation/navigation';
import { GeolocationCountrySelect } from '../components/geolocation-country-select/geolocation-country-select';

export const BusinessLocation = ( {
	sendEvent,
	navigationProgress,
	context,
}: {
	sendEvent: ( event: BusinessLocationEvent ) => void;
	navigationProgress: number;
	context: Pick<
		CoreProfilerStateMachineContext,
		'geolocatedLocation' | 'countries'
	>;
} ) => {
	const [ storeCountry, setStoreCountry ] = useState< CountryStateOption >( {
		key: '',
		label: '',
	} );

	const inputLabel = __( 'Select country/region', 'woocommerce' );

	return (
		<div
			className="woocommerce-profiler-business-location"
			data-testid="core-profiler-business-location"
		>
			<Navigation percentage={ navigationProgress } />
			<div className="woocommerce-profiler-page__content woocommerce-profiler-business-location__content">
				<Heading
					className="woocommerce-profiler__stepper-heading"
					title={ __(
						'Where is your business located?',
						'woocommerce'
					) }
					subTitle={ __(
						'We’ll use this information to help you set up payments, shipping, and taxes.',
						'woocommerce'
					) }
				/>
				<GeolocationCountrySelect
					countries={ context.countries }
					initialValue={ storeCountry }
					label={ inputLabel }
					geolocatedLocation={ context.geolocatedLocation }
					placeholder={ inputLabel }
					onChange={ ( countryStateOption ) => {
						setStoreCountry( countryStateOption );
					} }
					onRetry={ () => {
						// Retry geolocation
					} }
					onSkip={ () => {
						// Skip geolocation
					} }
				/>
				<div className="woocommerce-profiler-button-container woocommerce-profiler-go-to-mystore__button-container">
					<Button
						className="woocommerce-profiler-button"
						variant="primary"
						disabled={ ! storeCountry.key }
						onClick={ () => {
							sendEvent( {
								type: 'BUSINESS_LOCATION_COMPLETED',
								payload: {
									storeLocation: storeCountry.key,
								},
							} );
						} }
					>
						{ __( 'Go to my store', 'woocommerce' ) }
					</Button>
				</div>
			</div>
		</div>
	);
};
