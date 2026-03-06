/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import clsx from 'clsx';
import {
	PaymentsEntity,
	PaymentsProvider,
	paymentSettingsStore,
	WC_ADMIN_NAMESPACE,
	woopaymentsOnboardingStore,
} from '@woocommerce/data';
import { useDispatch } from '@wordpress/data';
import { useEffect, useMemo, useRef, useState } from '@wordpress/element';
import { decodeEntities } from '@wordpress/html-entities';
import { Popover } from '@wordpress/components';
import { Link } from '@woocommerce/components';
import { getAdminLink } from '@woocommerce/settings';
import InfoOutline from 'gridicons/dist/info-outline';
import interpolateComponents from '@automattic/interpolate-components';

/**
 * Internal dependencies
 */
import { CountrySelector } from '~/settings-payments/components/country-selector';
import { ListPlaceholder } from '~/settings-payments/components/list-placeholder';
import { PaymentGatewayList } from '~/settings-payments/components/payment-gateway-list';
import { recordPaymentsEvent } from '~/settings-payments/utils';

interface PaymentGatewaysProps {
	providers: PaymentsProvider[];
	installedPluginSlugs: string[];
	installingPlugin: string | null;
	/**
	 * Callback to set up the plugin.
	 *
	 * @param provider      Extension provider.
	 * @param onboardingUrl Extension onboarding URL (if available).
	 * @param attachUrl     Extension attach URL (if available).
	 * @param context       The context from which the plugin is set up (e.g. 'wc_settings_payments__main_suggestion').
	 */
	setUpPlugin: (
		provider: PaymentsEntity,
		onboardingUrl: string | null,
		attachUrl: string | null,
		context?: string
	) => void;
	acceptIncentive: ( id: string ) => void;
	shouldHighlightIncentive: boolean;
	updateOrdering: ( providers: PaymentsProvider[] ) => void;
	isFetching: boolean;
	businessRegistrationCountry: string | null;
	setBusinessRegistrationCountry: ( country: string ) => void;
	setIsOnboardingModalOpen: ( isOpen: boolean ) => void;
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
	setUpPlugin,
	acceptIncentive,
	shouldHighlightIncentive,
	updateOrdering,
	isFetching,
	businessRegistrationCountry,
	setBusinessRegistrationCountry,
	setIsOnboardingModalOpen,
}: PaymentGatewaysProps ) => {
	const { invalidateResolution: invalidateMainStore } =
		useDispatch( paymentSettingsStore );
	const { invalidateResolution: invalidateWooPaymentsOnboardingStore } =
		useDispatch( woopaymentsOnboardingStore );
	const [ isPopoverVisible, setIsPopoverVisible ] = useState( false );
	const buttonRef = useRef< HTMLDivElement >( null );
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

	const handleBusinessLocationIndicatorClick = (
		event: React.MouseEvent | React.KeyboardEvent
	) => {
		const clickedElement = event.target as HTMLElement;
		const parentDiv = clickedElement.closest(
			'.settings-payment-gateways__header-select-container--indicator'
		);

		if ( buttonRef.current && parentDiv !== buttonRef.current ) {
			return;
		}

		// Record the event when user clicks on the business location indicator.
		recordPaymentsEvent( 'business_location_indicator_click', {
			store_country: storeCountryCode,
			business_country: businessRegistrationCountry || 'unknown',
		} );

		setIsPopoverVisible( ( prev ) => ! prev );
	};

	const handleFocusOutside = () => {
		setIsPopoverVisible( false );
	};

	const handleIndicatorKeyDown = ( event: React.KeyboardEvent ) => {
		if ( event.key === 'Escape' && isPopoverVisible ) {
			event.stopPropagation();
			setIsPopoverVisible( false );
			buttonRef.current?.focus();
		} else if ( event.key === 'Enter' || event.key === ' ' ) {
			// Only handle Enter/Space when the indicator button itself is focused,
			// allowing links inside the popover to work normally.
			if ( event.target !== buttonRef.current ) {
				return;
			}
			event.preventDefault();
			handleBusinessLocationIndicatorClick( event );
		}
	};

	// Handle Escape key globally when popover is open (for portal focus)
	useEffect( () => {
		if ( ! isPopoverVisible ) {
			return;
		}

		const handleGlobalKeyDown = ( event: KeyboardEvent ) => {
			if ( event.key === 'Escape' ) {
				event.stopPropagation();
				setIsPopoverVisible( false );
				buttonRef.current?.focus();
			}
		};

		document.addEventListener( 'keydown', handleGlobalKeyDown );
		return () => {
			document.removeEventListener( 'keydown', handleGlobalKeyDown );
		};
	}, [ isPopoverVisible ] );

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
						onChange={ ( currentSelectedCountry: string ) => {
							// Save selected country and refresh the store by invalidating getPaymentProviders.
							apiFetch( {
								path:
									WC_ADMIN_NAMESPACE +
									'/settings/payments/country',
								method: 'POST',
								data: { location: currentSelectedCountry },
							} ).then( () => {
								// Update UI.
								setBusinessRegistrationCountry(
									currentSelectedCountry
								);
								// Update the window value - this will be updated by the backend on refresh but this keeps state persistent.
								if (
									window.wcSettings.admin
										.woocommerce_payments_nox_profile
								) {
									window.wcSettings.admin.woocommerce_payments_nox_profile.business_country_code =
										currentSelectedCountry;
								}
								invalidateMainStore( 'getPaymentProviders', [
									currentSelectedCountry,
								] );
								invalidateWooPaymentsOnboardingStore(
									'getOnboardingData',
									[]
								);
							} );
						} }
					/>
					{ isBaseCountryDifferent && (
						<div
							className="settings-payment-gateways__header-select-container--indicator"
							tabIndex={ 0 }
							role="button"
							ref={ buttonRef }
							onClick={ handleBusinessLocationIndicatorClick }
							onKeyDown={ handleIndicatorKeyDown }
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
									onFocusOutside={ handleFocusOutside }
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
															onClick={ () => {
																// Record the event when user clicks on the edit store location link.
																recordPaymentsEvent(
																	'business_location_popover_edit_store_location_click',
																	{
																		store_country:
																			storeCountryCode,
																		business_country:
																			businessRegistrationCountry ||
																			'unknown',
																	}
																);
															} }
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
					setUpPlugin={ setUpPlugin }
					acceptIncentive={ acceptIncentive }
					shouldHighlightIncentive={ shouldHighlightIncentive }
					updateOrdering={ updateOrdering }
					setIsOnboardingModalOpen={ setIsOnboardingModalOpen }
				/>
			) }
		</div>
	);
};
