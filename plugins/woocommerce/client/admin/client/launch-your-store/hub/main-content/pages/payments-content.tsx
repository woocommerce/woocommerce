/**
 * External dependencies
 */
import { useCallback } from 'react';
import apiFetch from '@wordpress/api-fetch';
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import React, { useEffect, useRef, useState } from '@wordpress/element';
import { pluginsStore, paymentSettingsStore } from '@woocommerce/data';
import { useDispatch, useSelect } from '@wordpress/data';
import { WooPaymentsMethodsLogos } from '@woocommerce/onboarding';

/**
 * Internal dependencies
 */
import WooPaymentsOnboarding from '~/settings-payments/onboarding/providers/woopayments/components/onboarding';
import { useOnboardingContext } from '~/settings-payments/onboarding/providers/woopayments/data/onboarding-context';
import { WC_ASSET_URL } from '~/utils/admin-settings';
import { createNoticesFromResponse } from '~/lib/notices';
import './payments-content.scss';
import { useSetUpPaymentsContext } from '~/launch-your-store/data/setup-payments-context';
import {
	recordPaymentsEvent,
	isWooPayments,
	recordPaymentsOnboardingEvent,
} from '~/settings-payments/utils';
import {
	wooPaymentsExtensionSlug,
	wooPaymentsProviderId,
	wooPaymentsSuggestionId,
	wooPaymentsOnboardingSessionEntryLYS,
} from '~/settings-payments/constants';

/**
 * Validates and sanitizes a plugin slug.
 * Plugin slugs should only contain lowercase letters, numbers, dashes, and underscores.
 * This prevents passing unexpected or malicious input to plugin installation.
 *
 * @param slug - The plugin slug to validate.
 * @return The validated slug if valid, or undefined if invalid.
 */
const validatePluginSlug = ( slug: unknown ): string | undefined => {
	// Must be a string.
	if ( typeof slug !== 'string' ) {
		return undefined;
	}

	const trimmed = slug.trim();

	// Must be non-empty after trimming.
	if ( trimmed.length === 0 ) {
		return undefined;
	}

	// Plugin slugs should only contain lowercase letters, numbers, dashes, and underscores.
	// This pattern rejects control characters, slashes, spaces, and other special characters.
	const validSlugPattern = /^[a-z0-9_-]+$/;
	if ( ! validSlugPattern.test( trimmed ) ) {
		// eslint-disable-next-line no-console
		console.warn(
			`[WooCommerce Payments] Invalid plugin slug format: "${ trimmed }". Using default slug.`
		);
		return undefined;
	}

	return trimmed;
};

const InstallWooPaymentsStep = ( {
	installWooPayments,
	isPluginInstalling,
	isPluginInstalled,
}: {
	installWooPayments: ( slug: string | undefined ) => void;
	isPluginInstalling: boolean;
	isPluginInstalled: boolean;
} ) => {
	// Track the step view.
	useEffect( () => {
		recordPaymentsOnboardingEvent(
			'woopayments_onboarding_modal_step_view',
			{
				step: 'install_woopayments',
				from: 'lys',
				source: wooPaymentsOnboardingSessionEntryLYS,
			}
		);
	}, [] );

	const isWooPayEligible = useSelect( ( select ) => {
		const store = select( paymentSettingsStore );
		return store.getIsWooPayEligible();
	}, [] );

	const wooPaymentsProvider = useSelect( ( select ) => {
		const store = select( paymentSettingsStore );
		return store
			.getPaymentProviders()
			.find( ( provider ) => isWooPayments( provider.id ) );
	}, [] );

	const businessCountry =
		window.wcSettings?.admin?.woocommerce_payments_nox_profile
			?.business_country_code || null;

	let buttonText = __( 'Install', 'woocommerce' );

	if ( isPluginInstalled && ! isPluginInstalling ) {
		buttonText = __( 'Enable', 'woocommerce' );
	}

	if ( isPluginInstalled && isPluginInstalling ) {
		buttonText = __( 'Enabling', 'woocommerce' );
	}

	if ( ! isPluginInstalled && isPluginInstalling ) {
		buttonText = __( 'Installing', 'woocommerce' );
	}

	return (
		<div className="launch-your-store-payments-content__step--install-woopayments">
			<div className="launch-your-store-payments-content__step--install-woopayments-logo">
				<img
					src={ `${ WC_ASSET_URL }images/woo-logo.svg` }
					alt=""
					role="presentation"
				/>
			</div>
			<h1 className="launch-your-store-payments-content__step--install-woopayments-title">
				{ __( 'Accept payments with Woo', 'woocommerce' ) }
			</h1>
			<p className="launch-your-store-payments-content__step--install-woopayments-description">
				{ __(
					'Set up payments for your store in just a few steps. With WooPayments, you can accept online and in-person payments, track revenue, and handle all payment activity from one place.',
					'woocommerce'
				) }
			</p>
			<div className="launch-your-store-payments-content__step--install-woopayments-logos">
				<WooPaymentsMethodsLogos
					maxElements={ 10 }
					isWooPayEligible={ isWooPayEligible }
				/>
			</div>
			<Button
				className="launch-your-store-payments-content__step--install-woopayments-button"
				onClick={ () => {
					// Preload the onboarding data in the background.
					if (
						wooPaymentsProvider?.onboarding?._links?.preload?.href
					) {
						// We don't need to await this call or handle its response.
						apiFetch( {
							url: wooPaymentsProvider?.onboarding?._links
								?.preload?.href,
							method: 'POST',
							data: {
								location: businessCountry,
							},
						} );
					}

					// Validate and sanitize the plugin slug before passing to installWooPayments.
					// This prevents unexpected or malicious input from reaching the plugin installer.
					// If validation fails, undefined is passed, which triggers the default slug fallback.
					const validatedSlug = validatePluginSlug(
						wooPaymentsProvider?.plugin?.slug
					);
					installWooPayments( validatedSlug );
				} }
				isBusy={ isPluginInstalling }
				disabled={ isPluginInstalling }
				variant="primary"
			>
				{ buttonText }
			</Button>
		</div>
	);
};

export const PaymentsContent = ( {} ) => {
	const {
		isWooPaymentsActive,
		isWooPaymentsInstalled,
		setWooPaymentsRecentlyActivated,
	} = useSetUpPaymentsContext();

	const { refreshStoreData } = useOnboardingContext();

	const [ isPluginInstalling, setIsPluginInstalling ] =
		useState< boolean >( false );
	const { installAndActivatePlugins } = useDispatch( pluginsStore );
	const isMountedRef = useRef( true );

	// Cleanup on unmount to prevent state updates after component is unmounted.
	useEffect( () => {
		return () => {
			isMountedRef.current = false;
		};
	}, [] );

	const installWooPayments = useCallback(
		( realPluginSlug: string | undefined ) => {
			// Set the plugin installation state to true to show a loading indicator.
			setIsPluginInstalling( true );

			recordPaymentsEvent( 'recommendations_setup', {
				extension_selected: wooPaymentsExtensionSlug, // Use the official slug, not the real one.
				extension_action: ! isWooPaymentsInstalled
					? 'install'
					: 'activate',
				provider_id: wooPaymentsProviderId,
				suggestion_id: wooPaymentsSuggestionId,
				provider_extension_slug: wooPaymentsExtensionSlug, // Use the official slug, not the real one.
				from: 'lys',
				source: wooPaymentsOnboardingSessionEntryLYS,
			} );

			// Install and activate the WooPayments plugin.
			installAndActivatePlugins( [
				realPluginSlug ?? wooPaymentsExtensionSlug,
			] )
				.then( async ( response ) => {
					if ( ! isMountedRef.current ) {
						return;
					}
					createNoticesFromResponse( response );
					setWooPaymentsRecentlyActivated( true );
					// Refresh store data after installation.
					// This will trigger a re-render and initialize the onboarding flow.
					refreshStoreData();

					if ( ! isWooPaymentsInstalled ) {
						// Record the extension installation event.
						recordPaymentsEvent( 'provider_installed', {
							provider_id: wooPaymentsProviderId,
							suggestion_id: wooPaymentsSuggestionId,
							provider_extension_slug: wooPaymentsExtensionSlug,
							from: 'lys',
							source: wooPaymentsOnboardingSessionEntryLYS,
						} );
					}
					// Note: The provider extension activation is tracked from the backend (the `provider_extension_activated` event).

					setIsPluginInstalling( false );
				} )
				.catch( ( response: { errors: Record< string, string > } ) => {
					if ( ! isMountedRef.current ) {
						return;
					}
					// Handle errors during installation
					let eventName = 'provider_extension_installation_failed';
					if ( isWooPaymentsInstalled ) {
						eventName = 'provider_extension_activation_failed';
					}
					recordPaymentsEvent( eventName, {
						provider_id: wooPaymentsProviderId,
						suggestion_id: wooPaymentsSuggestionId,
						provider_extension_slug: wooPaymentsExtensionSlug,
						from: 'lys',
						source: wooPaymentsOnboardingSessionEntryLYS,
						reason: 'error',
					} );
					createNoticesFromResponse( response );
					setIsPluginInstalling( false );
				} );
		},
		[
			isWooPaymentsInstalled,
			installAndActivatePlugins,
			setWooPaymentsRecentlyActivated,
			refreshStoreData,
		]
	);

	return (
		<div className="launch-your-store-payments-content">
			<div className="launch-your-store-payments-content__canvas">
				{ ! isWooPaymentsActive ? (
					<InstallWooPaymentsStep
						installWooPayments={ installWooPayments }
						isPluginInstalled={ isWooPaymentsInstalled }
						isPluginInstalling={ isPluginInstalling }
					/>
				) : (
					<WooPaymentsOnboarding includeSidebar={ false } />
				) }
			</div>
		</div>
	);
};
