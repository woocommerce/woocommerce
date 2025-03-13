/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import clsx from 'clsx';
import {
	PaymentProvider,
	paymentSettingsStore,
	WC_ADMIN_NAMESPACE,
} from '@woocommerce/data';
import { useDispatch } from '@wordpress/data';
import { useMemo, useState } from '@wordpress/element';
import { decodeEntities } from '@wordpress/html-entities';
import { Popover } from '@wordpress/components';
import { Link } from '@woocommerce/components';
import { getAdminLink } from '@woocommerce/settings';
import InfoOutline from 'gridicons/dist/info-outline';
import interpolateComponents from '@automattic/interpolate-components';
import { useDebounce } from '@wordpress/compose';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { CountrySelector } from '~/settings-payments/components/country-selector';
import { ListPlaceholder } from '~/settings-payments/components/list-placeholder';
import { PaymentGatewayList } from '~/settings-payments/components/payment-gateway-list';

interface PaymentGatewaysProps {
	providers: PaymentProvider[];
	installedPluginSlugs: string[];
	installingPlugin: string | null;
	setupPlugin: (
		id: string,
		slug: string,
		onboardingUrl: string | null
	) => void;
	acceptIncentive: ( id: string ) => void;
	updateOrdering: ( providers: PaymentProvider[] ) => void;
	isFetching: boolean;
	businessRegistrationCountry: string | null;
	setBusinessRegistrationCountry: ( country: string ) => void;
}

/**
 * A component for displaying and managing the list of payment providers. It includes a country selector
 * to filter providers based on the business location and supports real-time updates when the country or
 * provider order changes.
 */
export const PaymentGateways = ( {
	providers,
	installedPluginSlugs,
	installingPlugin,
	setupPlugin,
	acceptIncentive,
	updateOrdering,
	isFetching,
	businessRegistrationCountry,
	setBusinessRegistrationCountry,
}: PaymentGatewaysProps ) => {
	const { invalidateResolution } = useDispatch( paymentSettingsStore );
	const [ isPopoverVisible, setIsPopoverVisible ] = useState( false );
	const storeCountryCode = (
		window.wcSettings?.admin?.preloadSettings?.general
			?.woocommerce_default_country || 'US'
	).split( ':' )[ 0 ]; // Retrieve the default store country code, by removing the state code if present.

	/**
	 * Generates a list of country options from the WooCommerce settings.
	 */
	const countryOptions = useMemo( () => {
		return Object.entries( window.wcSettings.countries || [] )
			.map( ( [ key, name ] ) => ( {
				key,
				name: decodeEntities( name ),
				types: [],
			} ) )
			.sort( ( a, b ) => a.name.localeCompare( b.name ) );
	}, [] );

	const isBaseCountryDifferent =
		storeCountryCode !== businessRegistrationCountry;
	const selectContainerClass = clsx(
		'settings-payment-gateways__header-select-container',
		{
			'has-alert': isBaseCountryDifferent,
		}
	);

	const hidePopoverDebounced = useDebounce( () => {
		setIsPopoverVisible( false );
	}, 350 );
	const showPopover = () => {
		setIsPopoverVisible( true );
		hidePopoverDebounced.cancel();
	};

	return (
		<div className="settings-payment-gateways">
			<div className="settings-payment-gateways__header">
				<div className="settings-payment-gateways__header-title">
					{ __( 'Payment providers', 'woocommerce' ) }
				</div>
				<div className={ selectContainerClass }>
					<CountrySelector
						className="woocommerce-select-control__country"
						label={ __( 'Business location:', 'woocommerce' ) }
						placeholder={ '' }
						value={
							countryOptions.find(
								( country ) =>
									country.key === businessRegistrationCountry
							) ?? { key: 'US', name: 'United States (US)' }
						}
						options={ countryOptions }
						onChange={ ( value: string ) => {
							// Save selected country and refresh the store by invalidating getPaymentProviders.
							apiFetch( {
								path:
									WC_ADMIN_NAMESPACE +
									'/settings/payments/country',
								method: 'POST',
								data: { location: value },
							} ).then( () => {
								// Record the event when the country is changed.
								const previouslySelectedCountry =
									businessRegistrationCountry;
								const currentSelectedCountry = value;
								recordEvent(
									'settings_payments_business_location_update',
									{
										old_location: previouslySelectedCountry,
										new_location: currentSelectedCountry,
									}
								);

								// Update UI.
								setBusinessRegistrationCountry( value );
								invalidateResolution( 'getPaymentProviders', [
									value,
								] );
							} );
						} }
					/>
					{ isBaseCountryDifferent && (
						<div
							className="settings-payment-gateways__header-select-container--indicator"
							tabIndex={ 0 }
							role="button"
							onClick={ () =>
								setIsPopoverVisible( ! isPopoverVisible )
							}
							onMouseEnter={ showPopover }
							onMouseLeave={ hidePopoverDebounced }
							onKeyDown={ ( event ) => {
								if (
									event.key === 'Enter' ||
									event.key === ' '
								) {
									setIsPopoverVisible( ! isPopoverVisible );
								}
							} }
						>
							<div className="settings-payment-gateways__header-select-container--indicator-icon">
								<InfoOutline />
							</div>

							{ isPopoverVisible && (
								<Popover
									className="settings-payment-gateways__header-select-container--indicator-popover"
									placement="top-end"
									offset={ 4 }
									variant="unstyled"
									focusOnMount={ true }
									noArrow={ true }
									shift={ true }
									onClose={ hidePopoverDebounced }
								>
									<div className="components-popover__content-container">
										<p>
											{ interpolateComponents( {
												mixedString: __(
													'Your business location does not match your store location. {{link}}Edit store location.{{/link}}',
													'woocommerce'
												),
												components: {
													link: (
														<Link
															href={ getAdminLink(
																'admin.php?page=wc-settings&tab=general'
															) }
															target="_blank"
															type="external"
														/>
													),
												},
											} ) }
										</p>
									</div>
								</Popover>
							) }
						</div>
					) }
				</div>
			</div>
			{ isFetching ? (
				<ListPlaceholder rows={ 5 } />
			) : (
				<PaymentGatewayList
					providers={ providers }
					installedPluginSlugs={ installedPluginSlugs }
					installingPlugin={ installingPlugin }
					setupPlugin={ setupPlugin }
					acceptIncentive={ acceptIncentive }
					updateOrdering={ updateOrdering }
				/>
			) }
		</div>
	);
};
