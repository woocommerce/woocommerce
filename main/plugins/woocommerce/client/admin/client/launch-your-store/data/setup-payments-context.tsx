/**
 * External dependencies
 */
import { createContext, useContext, useState } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { pluginsStore, paymentSettingsStore } from '@woocommerce/data';
import { getNewPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { LYSPaymentsSteps } from '~/settings-payments/onboarding/providers/woopayments/steps';
import { OnboardingProvider } from '~/settings-payments/onboarding/providers/woopayments/data/onboarding-context';
import { isWooPayments } from '~/settings-payments/utils';
import { wooPaymentsExtensionSlug } from '~/settings-payments/constants';

interface SetUpPaymentsContextType {
	isWooPaymentsActive: boolean;
	isWooPaymentsInstalled: boolean;
	wooPaymentsRecentlyActivated: boolean;
	setWooPaymentsRecentlyActivated: ( value: boolean ) => void;
}

/**
 * Context to manage onboarding steps
 */
const SetUpPaymentsContext = createContext< SetUpPaymentsContextType >( {
	isWooPaymentsActive: false,
	isWooPaymentsInstalled: false,
	wooPaymentsRecentlyActivated: false,
	setWooPaymentsRecentlyActivated: () => undefined,
} );

export const useSetUpPaymentsContext = () => useContext( SetUpPaymentsContext );

export const SetUpPaymentsProvider: React.FC< {
	children: React.ReactNode;
	closeModal: () => void;
} > = ( { children, closeModal } ) => {
	// Get the WooPayments provider to access the real plugin slug.
	// This is important for test/beta versions that may be installed under a different slug.
	// We wait for the fetch to complete before exposing state to prevent slug instability.
	const { wooPaymentsPluginSlug, isSlugResolved } = useSelect(
		( select ) => {
			const store = select( paymentSettingsStore );
			const isFetching = store.isFetching();
			const providers = store.getPaymentProviders();

			// Defensively check that providers is an array before calling .find().
			// This prevents runtime errors if getPaymentProviders() returns null/undefined.
			const wooPaymentsProvider = Array.isArray( providers )
				? providers.find( ( provider ) => isWooPayments( provider.id ) )
				: undefined;

			// Return both the slug and resolution state.
			// We consider the slug "resolved" when:
			// 1. We're not fetching anymore, AND
			// 2. Either we found a provider with a slug, or providers loaded but WooPayments isn't present
			const hasLoadedProviders =
				! isFetching && Array.isArray( providers );
			const resolvedSlug = wooPaymentsProvider?.plugin?.slug;

			return {
				wooPaymentsPluginSlug: resolvedSlug ?? wooPaymentsExtensionSlug,
				isSlugResolved: hasLoadedProviders,
			};
		},
		// Empty deps array - the selector subscribes to store state internally.
		// It re-runs automatically when store state changes (isFetching, providers).
		[]
	);

	// Check if WooPayments is active by looking for the plugin in the active plugins list.
	const isWooPaymentsActive = useSelect(
		( select ) => {
			const activePlugins = select( pluginsStore ).getActivePlugins();
			// Defensively check that activePlugins is an array before calling .includes().
			return Array.isArray( activePlugins )
				? activePlugins.includes( wooPaymentsPluginSlug )
				: false;
		},
		[ wooPaymentsPluginSlug ]
	);

	const isWooPaymentsInstalled = useSelect(
		( select ) => {
			const installedPlugins =
				select( pluginsStore ).getInstalledPlugins();
			// Defensively check that installedPlugins is an array before calling .includes().
			return Array.isArray( installedPlugins )
				? installedPlugins.includes( wooPaymentsPluginSlug )
				: false;
		},
		[ wooPaymentsPluginSlug ]
	);

	// State to track if WooPayments was recently enabled
	const [ wooPaymentsRecentlyActivated, setWooPaymentsRecentlyActivated ] =
		useState< boolean >( false );

	// Custom URL strategy for LYS that preserves sidebar and content params when navigation is forced by the OnboardingProvider.
	const lysUrlStrategy = {
		buildStepURL: (
			stepPath: string,
			preservedParams: Record< string, string > = {}
		) => {
			return getNewPath(
				{
					path: stepPath,
					...preservedParams,
				},
				'/launch-your-store' + stepPath,
				{
					page: 'wc-admin',
					path: '/launch-your-store/woopayments/onboarding',
					sidebar: 'hub',
					content: 'payments',
				}
			);
		},
		preserveParams: [ 'sidebar', 'content' ],
	};

	// Wait for slug resolution to prevent state flipping.
	// During initial load, we don't know if a test/beta version is installed under a different slug.
	// Rendering children without waiting could show incorrect UI that then flips after load.
	if ( ! isSlugResolved ) {
		// Render children without context-dependent decisions during loading.
		// This prevents the UI from making incorrect assumptions about WooPayments state.
		return (
			<SetUpPaymentsContext.Provider
				value={ {
					isWooPaymentsActive: false,
					isWooPaymentsInstalled: false,
					wooPaymentsRecentlyActivated: false,
					setWooPaymentsRecentlyActivated: () => undefined,
				} }
			>
				{ children }
			</SetUpPaymentsContext.Provider>
		);
	}

	return (
		<SetUpPaymentsContext.Provider
			value={ {
				isWooPaymentsActive,
				isWooPaymentsInstalled,
				wooPaymentsRecentlyActivated,
				setWooPaymentsRecentlyActivated,
			} }
		>
			{ isWooPaymentsActive && (
				<OnboardingProvider
					closeModal={ closeModal }
					onboardingSteps={ LYSPaymentsSteps }
					urlStrategy={ lysUrlStrategy }
					sessionEntryPoint="lys" // This should match the value of WooPaymentsService::SESSION_ENTRY_LYS.
					onFinish={ closeModal }
				>
					{ children }
				</OnboardingProvider>
			) }
			{ ! isWooPaymentsActive && children }
		</SetUpPaymentsContext.Provider>
	);
};
