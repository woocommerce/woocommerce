/**
 * External dependencies
 */
import { useCallback, useRef } from 'react';
import { __, sprintf } from '@wordpress/i18n';
import {
	pluginsStore,
	paymentSettingsStore,
	PaymentProvider,
} from '@woocommerce/data';
import { resolveSelect, useDispatch, useSelect } from '@wordpress/data';
import React, { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { getHistory, getNewPath } from '@woocommerce/navigation';
import { recordEvent } from '@woocommerce/tracks';
import { Button } from '@wordpress/components';

/**
 * Internal dependencies
 */
import './settings-payments-main.scss';
import './settings-payments-body.scss';
import { createNoticesFromResponse } from '~/lib/notices';
import { OtherPaymentGateways } from '~/settings-payments/components/other-payment-gateways';
import { PaymentGateways } from '~/settings-payments/components/payment-gateways';
import { IncentiveBanner } from '~/settings-payments/components/incentive-banner';
import { IncentiveModal } from '~/settings-payments/components/incentive-modal';
import {
	providersContainWooPaymentsInTestMode,
	providersContainWooPaymentsInDevMode,
	isIncentiveDismissedInContext,
	isSwitchIncentive,
	isWooPayments,
	getWooPaymentsTestDriveAccountLink,
} from '~/settings-payments/utils';
import { WooPaymentsPostSandboxAccountSetupModal } from '~/settings-payments/components/modals';
import { getAdminSetting } from '~/utils/admin-settings';

/**
 * A component that renders the main settings page for managing payment gateways in WooCommerce.
 * It handles fetching and displaying payment providers, managing plugin installations, and
 * displaying incentive banners or modals when applicable.
 */
export const SettingsPaymentsMain = () => {
	const [ installingPlugin, setInstallingPlugin ] = useState< string | null >(
		null
	);
	// State to hold the sorted providers in case of changing the order, otherwise it will be null
	const [ sortedProviders, setSortedProviders ] = useState<
		PaymentProvider[] | null
	>( null );
	const { installAndActivatePlugins } = useDispatch( pluginsStore );
	const { updateProviderOrdering } = useDispatch( paymentSettingsStore );
	const [ errorMessage, setErrorMessage ] = useState< string | null >( null );
	const [
		postSandboxAccountSetupModalVisible,
		setPostSandboxAccountSetupModalVisible,
	] = useState( false );

	const [ storeCountry, setStoreCountry ] = useState< string | null >(
		window.wcSettings?.admin?.woocommerce_payments_nox_profile
			?.business_country_code || null
	);

	const assetUrl = getAdminSetting( 'wcAdminAssetUrl' );

	useEffect( () => {
		// Record the page view event
		recordEvent( 'settings_payments_pageview' );

		// Handle URL parameters and display messages or modals.
		const urlParams = new URLSearchParams( window.location.search );
		const isAccountTestDriveError =
			urlParams.get( 'test_drive_error' ) === 'true';
		if ( isAccountTestDriveError ) {
			setErrorMessage(
				sprintf(
					/* translators: %s: plugin name */
					__(
						'%s: An error occurred while setting up your sandbox account — please try again.',
						'woocommerce'
					),
					'WooPayments'
				)
			);
		}

		const isJetpackConnectionError =
			urlParams.get( 'wcpay-connect-jetpack-error' ) === '1';

		if ( isJetpackConnectionError ) {
			setErrorMessage(
				sprintf(
					/* translators: %s: plugin name */
					__(
						'%s: There was a problem connecting your WordPress.com account — please try again.',
						'woocommerce'
					),
					'WooPayments'
				)
			);
		}

		const isSandboxOnboardedSuccessful =
			urlParams.get( 'wcpay-sandbox-success' ) === 'true';

		if ( isSandboxOnboardedSuccessful ) {
			setPostSandboxAccountSetupModalVisible( true );
		}
	}, [] );

	const installedPluginSlugs = useSelect( ( select ) => {
		return select( pluginsStore ).getInstalledPlugins();
	}, [] );

	// Make UI refresh when plugin is installed.
	const { invalidateResolutionForStoreSelector } =
		useDispatch( paymentSettingsStore );

	const {
		providers,
		offlinePaymentGateways,
		suggestions,
		suggestionCategories,
		isFetching,
	} = useSelect(
		( select ) => {
			const paymentSettings = select( paymentSettingsStore );

			return {
				providers: paymentSettings.getPaymentProviders( storeCountry ),
				offlinePaymentGateways:
					paymentSettings.getOfflinePaymentGateways(),
				suggestions: paymentSettings.getSuggestions(),
				suggestionCategories: paymentSettings.getSuggestionCategories(),
				isFetching: paymentSettings.isFetching(),
			};
		},
		[ storeCountry ]
	);

	const dismissIncentive = useCallback(
		( dismissHref: string, context: string ) => {
			// The dismissHref is the full URL to dismiss the incentive.
			apiFetch( {
				url: dismissHref,
				method: 'POST',
				data: {
					context,
				},
			} );
		},
		[]
	);

	const acceptIncentive = useCallback( ( id: string ) => {
		apiFetch( {
			path: `/wc-analytics/admin/notes/experimental-activate-promo/${ id }`,
			method: 'POST',
		} );
	}, [] );

	/**
	 * Clear sortedProviders when data store updates.
	 */
	useEffect( () => {
		setSortedProviders( null );
	}, [ providers ] );

	function handleOrderingUpdate( sorted: PaymentProvider[] ) {
		// Extract the existing _order values in the sorted order
		const updatedOrderValues = sorted
			.map( ( provider ) => provider._order )
			.sort( ( a, b ) => a - b );

		// Build the orderMap by assigning the sorted _order values
		const orderMap: Record< string, number > = {};
		sorted.forEach( ( provider, index ) => {
			orderMap[ provider.id ] = updatedOrderValues[ index ];
		} );

		updateProviderOrdering( orderMap );

		// Set the sorted providers to the state to give a real-time update
		setSortedProviders( sorted );
	}

	const incentiveProvider = providers.find(
		( provider: PaymentProvider ) => '_incentive' in provider
	);
	const incentive = incentiveProvider ? incentiveProvider._incentive : null;

	// Determine what type of incentive surface to display.
	let showModalIncentive = false;
	let showBannerIncentive = false;
	if ( incentiveProvider && incentive ) {
		if ( isSwitchIncentive( incentive ) ) {
			if (
				! isIncentiveDismissedInContext(
					incentive,
					'wc_settings_payments__modal'
				)
			) {
				showModalIncentive = true;
			} else if (
				! isIncentiveDismissedInContext(
					incentive,
					'wc_settings_payments__banner'
				)
			) {
				showBannerIncentive = true;
			}
		} else if (
			! isIncentiveDismissedInContext(
				incentive,
				'wc_settings_payments__banner'
			)
		) {
			showBannerIncentive = true;
		}
	}

	const triggeredPageViewRef = useRef( false );

	// Record a pageview event when the page loads.
	useEffect( () => {
		if (
			isFetching ||
			! providers.length ||
			! suggestions.length ||
			triggeredPageViewRef.current
		) {
			return;
		}

		// Set the ref to true to prevent multiple pageview events.
		triggeredPageViewRef.current = true;

		const eventProps: { [ key: string ]: boolean } = {
			woocommerce_payments_displayed: providers.some( ( provider ) =>
				isWooPayments( provider.id )
			),
		};

		suggestions.forEach( ( suggestion ) => {
			eventProps[ suggestion.id.replace( /-/g, '_' ) + '_displayed' ] =
				true;
		} );

		providers
			.filter( ( provider ) => provider._type === 'suggestion' )
			.forEach( ( provider ) => {
				if ( provider._suggestion_id ) {
					eventProps[
						provider._suggestion_id.replace( /-/g, '_' ) +
							'_displayed'
					] = true;
				} else if ( provider.plugin && provider.plugin.slug ) {
					// Fallback to using the slug if the suggestion ID is not available.
					eventProps[
						provider.plugin.slug.replace( /-/g, '_' ) + '_displayed'
					] = true;
				}
			} );

		recordEvent( 'settings_payments_recommendations_pageview', eventProps );
	}, [ suggestions, providers, isFetching ] );

	const setupPlugin = useCallback(
		( id: string, slug: string, onboardingUrl: string | null ) => {
			if ( installingPlugin ) {
				return;
			}

			// A fail-safe to ensure that the onboarding URL is set for Woo Payments.
			// Note: We should get rid this sooner rather than later!
			if ( ! onboardingUrl && isWooPayments( id ) ) {
				onboardingUrl = getWooPaymentsTestDriveAccountLink();
			}

			setInstallingPlugin( id );
			recordEvent( 'settings_payments_recommendations_setup', {
				extension_selected: slug,
			} );
			installAndActivatePlugins( [ slug ] )
				.then( async ( response ) => {
					createNoticesFromResponse( response );
					invalidateResolutionForStoreSelector(
						'getPaymentProviders'
					);

					// Record the plugin installation event.
					recordEvent( 'settings_payments_provider_installed', {
						provider_id: id,
					} );

					// Wait for the state update and fetch the latest providers.
					const updatedProviders = await resolveSelect(
						paymentSettingsStore
					).getPaymentProviders( storeCountry );

					// Find the matching provider the updated list.
					const updatedProvider = updatedProviders.find(
						( provider: PaymentProvider ) =>
							provider.id === id ||
							provider?._suggestion_id === id || // For suggestions that were replaced by a gateway.
							provider.plugin.slug === slug // Last resort to find the provider.
					);

					// Record the event when user successfully enables a gateway.
					recordEvent( 'settings_payments_provider_enable', {
						provider_id: id,
					} );

					// If the installed and/or activated extension has recommended payment methods,
					// redirect to the payment methods page.
					if (
						(
							updatedProvider?.onboarding
								?.recommended_payment_methods ?? []
						).length > 0
					) {
						const history = getHistory();
						history.push( getNewPath( {}, '/payment-methods' ) );

						setInstallingPlugin( null );
						return;
					}

					setInstallingPlugin( null );

					if ( onboardingUrl ) {
						window.location.href = onboardingUrl;
					}
				} )
				.catch( ( response: { errors: Record< string, string > } ) => {
					createNoticesFromResponse( response );
					setInstallingPlugin( null );
				} );
		},
		[
			installingPlugin,
			installAndActivatePlugins,
			invalidateResolutionForStoreSelector,
			storeCountry,
		]
	);

	const trackMorePaymentsOptionsClicked = () => {
		// We will gather all the available payment methods (suggestions, gateways, offline PMs)
		// to track which options the user has.
		const paymentOptionsList: string[] = providers.map( ( provider ) => {
			if ( provider.plugin && provider.plugin.slug ) {
				return provider.plugin.slug.replace( /-/g, '_' );
			} else if ( provider._suggestion_id ) {
				return provider._suggestion_id.replace( /-/g, '_' );
			}

			return provider.id;
		} );

		offlinePaymentGateways.forEach( ( offlinePaymentGateway ) => {
			paymentOptionsList.push( offlinePaymentGateway.id );
		} );

		suggestions.forEach( ( suggestion ) => {
			if ( suggestion.plugin && suggestion.plugin.slug ) {
				paymentOptionsList.push(
					suggestion.plugin.slug.replace( /-/g, '_' )
				);
				return;
			}
			paymentOptionsList.push( suggestion.id.replace( /-/g, '_' ) );
		} );

		const uniquePaymentsOptions = [ ...new Set( paymentOptionsList ) ];

		recordEvent( 'settings_payments_recommendations_other_options', {
			available_payment_methods: uniquePaymentsOptions.join( ', ' ),
		} );
	};

	const morePaymentOptionsLink = (
		<Button
			variant={ 'link' }
			target="_blank"
			href="https://woocommerce.com/product-category/woocommerce-extensions/payment-gateways/"
			className="more-payment-options-link"
			onClick={ trackMorePaymentsOptionsClicked }
		>
			<img src={ assetUrl + '/icons/external-link.svg' } alt="" />
			{ __( 'More payment options', 'woocommerce' ) }
		</Button>
	);

	return (
		<>
			{ showModalIncentive && incentiveProvider && incentive && (
				<IncentiveModal
					incentive={ incentive }
					provider={ incentiveProvider }
					onboardingUrl={
						incentiveProvider.onboarding?._links.onboard.href ??
						null
					}
					onDismiss={ dismissIncentive }
					onAccept={ acceptIncentive }
					setupPlugin={ setupPlugin }
				/>
			) }
			{ errorMessage && (
				<div className="notice notice-error is-dismissible wcpay-settings-notice">
					<p>{ errorMessage }</p>
					<button
						type="button"
						className="notice-dismiss"
						onClick={ () => {
							setErrorMessage( null );
						} }
					></button>
				</div>
			) }
			{ showBannerIncentive && incentiveProvider && incentive && (
				<IncentiveBanner
					incentive={ incentive }
					provider={ incentiveProvider }
					onboardingUrl={
						incentiveProvider.onboarding?._links.onboard.href ??
						null
					}
					onDismiss={ dismissIncentive }
					onAccept={ acceptIncentive }
					setupPlugin={ setupPlugin }
				/>
			) }
			<div className="settings-payments-main__container">
				<PaymentGateways
					providers={ sortedProviders || providers }
					installedPluginSlugs={ installedPluginSlugs }
					installingPlugin={ installingPlugin }
					setupPlugin={ setupPlugin }
					acceptIncentive={ acceptIncentive }
					updateOrdering={ handleOrderingUpdate }
					isFetching={ isFetching }
					businessRegistrationCountry={ storeCountry }
					setBusinessRegistrationCountry={ setStoreCountry }
				/>
				{
					// If no suggestions are available, only show a link to the WooCommerce.com payment marketplace page.
					! isFetching && suggestions.length === 0 && (
						<div className="more-payment-options">
							{ morePaymentOptionsLink }
						</div>
					)
				}
				{ ( isFetching || suggestions.length > 0 ) && (
					<OtherPaymentGateways
						suggestions={ suggestions }
						suggestionCategories={ suggestionCategories }
						installingPlugin={ installingPlugin }
						setupPlugin={ setupPlugin }
						isFetching={ isFetching }
						morePaymentOptionsLink={ morePaymentOptionsLink }
					/>
				) }
			</div>
			<WooPaymentsPostSandboxAccountSetupModal
				isOpen={
					postSandboxAccountSetupModalVisible &&
					providersContainWooPaymentsInTestMode( providers )
				}
				devMode={ providersContainWooPaymentsInDevMode( providers ) }
				onClose={ () =>
					setPostSandboxAccountSetupModalVisible( false )
				}
			/>
		</>
	);
};

export default SettingsPaymentsMain;
