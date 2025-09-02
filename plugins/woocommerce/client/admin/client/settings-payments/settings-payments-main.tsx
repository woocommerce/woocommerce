/**
 * External dependencies
 */
import { useCallback, useRef } from 'react';
import { __, sprintf } from '@wordpress/i18n';
import {
	pluginsStore,
	paymentSettingsStore,
	PaymentsProvider,
	PaymentsEntity,
} from '@woocommerce/data';
import { resolveSelect, useDispatch, useSelect } from '@wordpress/data';
import React, { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { getHistory, getNewPath } from '@woocommerce/navigation';
import { getAdminLink } from '@woocommerce/settings';

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
	getWooPaymentsFromProviders,
	providersContainWooPaymentsNeedsSetup,
	getWooPaymentsTestDriveAccountLink,
	isIncentiveDismissedEarlierThanTimestamp,
	isActionIncentive,
	recordPaymentsEvent,
	recordPaymentsOnboardingEvent,
} from '~/settings-payments/utils';
import { WooPaymentsPostSandboxAccountSetupModal } from '~/settings-payments/components/modals';
import WooPaymentsModal from '~/settings-payments/onboarding/providers/woopayments';
import { TrackedLink } from '~/components/tracked-link/tracked-link';
import { isFeatureEnabled } from '~/utils/features';
import { wooPaymentsOnboardingSessionEntrySettings } from '~/settings-payments/constants';

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
		PaymentsProvider[] | null
	>( null );
	const { installAndActivatePlugins } = useDispatch( pluginsStore );
	const { updateProviderOrdering, attachPaymentExtensionSuggestion } =
		useDispatch( paymentSettingsStore );
	const [ errorMessage, setErrorMessage ] = useState< string | null >( null );
	const [
		postSandboxAccountSetupModalVisible,
		setPostSandboxAccountSetupModalVisible,
	] = useState( false );

	const [ businessCountry, setBusinessCountry ] = useState< string | null >(
		window.wcSettings?.admin?.woocommerce_payments_nox_profile
			?.business_country_code || null
	);

	const [ isOnboardingModalOpen, setIsOnboardingModalOpen ] =
		useState( false );

	useEffect( () => {
		// Record the page view event.
		recordPaymentsEvent( 'pageview' );

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

	// Used to invalidate the API data for the Payments Settings page.
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
				providers:
					paymentSettings.getPaymentProviders( businessCountry ),
				offlinePaymentGateways:
					paymentSettings.getOfflinePaymentGateways(
						businessCountry
					),
				suggestions: paymentSettings.getSuggestions( businessCountry ),
				suggestionCategories:
					paymentSettings.getSuggestionCategories( businessCountry ),
				isFetching: paymentSettings.isFetching(),
			};
		},
		[ businessCountry ]
	);

	const dismissIncentive = useCallback(
		( dismissHref: string, context: string, doNotTrack = false ) => {
			// The dismissHref is the full URL to dismiss the incentive.
			apiFetch( {
				url: dismissHref,
				method: 'POST',
				data: {
					context,
					do_not_track: doNotTrack,
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

	function handleOrderingUpdate( sorted: PaymentsProvider[] ) {
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
		( provider: PaymentsProvider ) => '_incentive' in provider
	);
	const incentive = incentiveProvider ? incentiveProvider._incentive : null;

	// Determine what type of incentive surface to display.
	let showModalIncentive = false;
	let showBannerIncentive = false;
	let shouldHighlightIncentive = false;
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
				const referenceTimestamp = new Date();
				referenceTimestamp.setDate( referenceTimestamp.getDate() - 30 );
				// If the merchant dismissed the Switch incentive modal more than 30 days ago,
				// show the banner instead of just highlighting the incentive.
				// @see its server brother in plugins/woocommerce/src/Internal/Admin/Settings/PaymentsController::store_has_providers_with_incentive()
				// for the admin menu red dot notice logic.
				if (
					isIncentiveDismissedEarlierThanTimestamp(
						incentive,
						'wc_settings_payments__modal',
						referenceTimestamp.getTime()
					)
				) {
					showBannerIncentive = true;
				} else {
					shouldHighlightIncentive = true;
				}
			}
		} else if ( isActionIncentive( incentive ) ) {
			if (
				! isIncentiveDismissedInContext(
					incentive,
					'wc_settings_payments__banner'
				)
			) {
				showBannerIncentive = true;
			} else {
				shouldHighlightIncentive = true;
			}
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

		// This prop is for historical data uniformity. WooPayments will also be recorded as a suggestion.
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

		recordPaymentsEvent( 'recommendations_pageview', eventProps );
	}, [ suggestions, providers, isFetching ] );

	const setUpPlugin = useCallback(
		(
			paymentsEntity: PaymentsEntity,
			onboardingUrl: string | null,
			attachUrl: string | null,
			context = 'wc_settings_payments__main'
		) => {
			if ( installingPlugin ) {
				return;
			}

			if ( paymentsEntity?.onboarding?._links?.preload?.href ) {
				// We are not interested in the response; we just want to trigger the preload.
				apiFetch( {
					url: paymentsEntity?.onboarding?._links?.preload.href,
					method: 'POST',
					data: {
						location: businessCountry,
					},
				} );
			}

			// A fail-safe to ensure that the onboarding URL is set for WooPayments.
			// Note: We should get rid of this sooner rather than later!
			if ( ! onboardingUrl && isWooPayments( paymentsEntity.id ) ) {
				onboardingUrl = getWooPaymentsTestDriveAccountLink();
			}

			setInstallingPlugin( paymentsEntity.id );
			recordPaymentsEvent( 'recommendations_setup', {
				extension_selected: paymentsEntity.plugin.slug,
				extension_action:
					paymentsEntity.plugin.status === 'not_installed'
						? 'install'
						: 'activate',
				provider_id: paymentsEntity.id,
				suggestion_id: paymentsEntity?._suggestion_id ?? 'unknown',
				provider_extension_slug: paymentsEntity.plugin.slug,
				from: context,
				source: context,
			} );
			installAndActivatePlugins( [ paymentsEntity.plugin.slug ] )
				.then( async ( response ) => {
					if ( attachUrl ) {
						attachPaymentExtensionSuggestion( attachUrl );
					}

					createNoticesFromResponse( response );
					invalidateResolutionForStoreSelector(
						'getPaymentProviders'
					);

					if ( paymentsEntity.plugin.status === 'not_installed' ) {
						// Record the extension installation event.
						recordPaymentsEvent( 'provider_installed', {
							provider_id: paymentsEntity.id,
							suggestion_id:
								paymentsEntity?._suggestion_id ?? 'unknown',
							provider_extension_slug: paymentsEntity.plugin.slug,
							from: context,
						} );
					}
					// Note: The provider extension activation is tracked from the backend (the `provider_extension_activated` event).

					setInstallingPlugin( null );

					// Wait for the state update and fetch the latest providers.
					const updatedProviders = await resolveSelect(
						paymentSettingsStore
					).getPaymentProviders( businessCountry );

					// Find the matching provider in the updated list.
					const updatedPaymentsEntity = updatedProviders.find(
						( current: PaymentsProvider ) =>
							current.id === paymentsEntity.id ||
							current?._suggestion_id === paymentsEntity.id || // For suggestions that were replaced by a gateway.
							current.plugin.slug === paymentsEntity.plugin.slug // Last resort to find the provider.
					);

					/**
					 * If the onboarding type is 'native_in_context', we need to open the WooPayments onboarding modal.
					 * Otherwise, we redirect to the onboarding URL or the payment methods page.
					 */
					if (
						updatedPaymentsEntity?.onboarding?.type ===
						'native_in_context'
					) {
						recordPaymentsOnboardingEvent(
							'woopayments_onboarding_modal_opened',
							{
								from: context,
								source: wooPaymentsOnboardingSessionEntrySettings,
							}
						);
						setIsOnboardingModalOpen( true );
					} else {
						// If the installed and/or activated extension has recommended payment methods,
						// redirect to the payment methods page.
						if (
							(
								updatedPaymentsEntity?.onboarding
									?.recommended_payment_methods ?? []
							).length > 0
						) {
							const history = getHistory();
							history.push(
								getNewPath( {}, '/payment-methods' )
							);

							return;
						}

						if ( onboardingUrl ) {
							window.location.href = onboardingUrl;
						}
					}
				} )
				.catch( ( response: { errors: Record< string, string > } ) => {
					let eventName = 'provider_extension_installation_failed';
					if ( paymentsEntity.plugin.status !== 'not_installed' ) {
						eventName = 'provider_extension_activation_failed';
					}
					recordPaymentsEvent( eventName, {
						provider_id: paymentsEntity.id,
						suggestion_id:
							paymentsEntity?._suggestion_id ?? 'unknown',
						provider_extension_slug: paymentsEntity.plugin.slug,
						from: context,
						source: wooPaymentsOnboardingSessionEntrySettings,
						reason: 'error',
					} );
					createNoticesFromResponse( response );
					setInstallingPlugin( null );
				} );
		},
		[
			installingPlugin,
			installAndActivatePlugins,
			invalidateResolutionForStoreSelector,
			businessCountry,
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

		recordPaymentsEvent( 'recommendations_other_options', {
			available_payment_methods: uniquePaymentsOptions.join( ', ' ),
		} );
	};

	const morePaymentOptionsLink = (
		<TrackedLink
			message={ __(
				// translators: {{Link}} is a placeholder for a html element.
				'{{Link}}More payment options{{/Link}}',
				'woocommerce'
			) }
			onClickCallback={ trackMorePaymentsOptionsClicked }
			targetUrl={
				isFeatureEnabled( 'marketplace' )
					? getAdminLink(
							'admin.php?page=wc-admin&tab=extensions&path=/extensions&category=payment-gateways'
					  )
					: 'https://woocommerce.com/product-category/woocommerce-extensions/payment-gateways/'
			}
			linkType={
				isFeatureEnabled( 'marketplace' ) ? 'wc-admin' : 'external'
			}
		/>
	);

	return (
		<>
			{ showModalIncentive && incentiveProvider && incentive && (
				<IncentiveModal
					incentive={ incentive }
					provider={ incentiveProvider }
					onboardingUrl={
						incentiveProvider.onboarding?._links?.onboard?.href ??
						null
					}
					onDismiss={ dismissIncentive }
					onAccept={ acceptIncentive }
					setUpPlugin={ setUpPlugin }
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
						incentiveProvider.onboarding?._links?.onboard?.href ??
						null
					}
					onDismiss={ dismissIncentive }
					onAccept={ acceptIncentive }
					setUpPlugin={ setUpPlugin }
				/>
			) }
			<div className="settings-payments-main__container">
				<PaymentGateways
					providers={ sortedProviders || providers }
					installedPluginSlugs={ installedPluginSlugs }
					installingPlugin={ installingPlugin }
					setUpPlugin={ setUpPlugin }
					acceptIncentive={ acceptIncentive }
					shouldHighlightIncentive={ shouldHighlightIncentive }
					updateOrdering={ handleOrderingUpdate }
					isFetching={ isFetching }
					businessRegistrationCountry={ businessCountry }
					setBusinessRegistrationCountry={ setBusinessCountry }
					setIsOnboardingModalOpen={ setIsOnboardingModalOpen }
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
						setUpPlugin={ setUpPlugin }
						isFetching={ isFetching }
						morePaymentOptionsLink={ morePaymentOptionsLink }
					/>
				) }
			</div>
			{ ( providersContainWooPaymentsNeedsSetup( providers ) ||
				providersContainWooPaymentsInTestMode( providers ) ) && (
				<WooPaymentsModal
					isOpen={ isOnboardingModalOpen }
					setIsOpen={ setIsOnboardingModalOpen }
					providerData={
						getWooPaymentsFromProviders( providers ) ||
						( {} as PaymentsProvider )
					}
				/>
			) }
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
