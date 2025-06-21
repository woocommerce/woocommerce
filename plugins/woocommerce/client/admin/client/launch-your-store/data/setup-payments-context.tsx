/**
 * External dependencies
 */
import { createContext, useContext, useState } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { pluginsStore } from '@woocommerce/data';
import { getNewPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { LYSPaymentsSteps } from '~/settings-payments/onboarding/providers/woopayments/steps';
import { OnboardingProvider } from '~/settings-payments/onboarding/providers/woopayments/data/onboarding-context';

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
	// Check if WooPayments is active by looking for the plugin in the active plugins list
	const isWooPaymentsActive = useSelect(
		( select ) =>
			select( pluginsStore )
				.getActivePlugins()
				.includes( 'woocommerce-payments' ),
		[]
	);

	const isWooPaymentsInstalled = useSelect(
		( select ) =>
			select( pluginsStore )
				.getInstalledPlugins()
				.includes( 'woocommerce-payments' ),
		[]
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
					source="launch-your-store"
					onFinish={ closeModal }
				>
					{ children }
				</OnboardingProvider>
			) }
			{ ! isWooPaymentsActive && children }
		</SetUpPaymentsContext.Provider>
	);
};
