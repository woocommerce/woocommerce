/**
 * External dependencies
 */
import {
	createContext,
	useContext,
	useState,
	useEffect,
} from '@wordpress/element';
import { getNewPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { LYSPaymentsSteps } from '~/settings-payments/onboarding/providers/woopayments/steps';
import { OnboardingProvider } from '~/settings-payments/onboarding/providers/woopayments/data/onboarding-context';
import { getPaymentsTaskFromLysTasklist } from '../hub/sidebar/tasklist';

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
	// Extract WooPayments state from the payments task's additionalData using getLysTasklist
	const [ isWooPaymentsActive, setIsWooPaymentsActive ] =
		useState< boolean >( false );
	const [ isWooPaymentsInstalled, setIsWooPaymentsInstalled ] =
		useState< boolean >( false );

	useEffect( () => {
		let isMounted = true;

		const fetchWooPaymentsStatus = async () => {
			const paymentsTask = await getPaymentsTaskFromLysTasklist();

			// Validate paymentsTask.additionalData has expected properties
			if ( paymentsTask?.additionalData && isMounted ) {
				const { wooPaymentsIsActive, wooPaymentsIsInstalled } =
					paymentsTask.additionalData;

				// Validate boolean-like values before setting state
				setIsWooPaymentsActive(
					typeof wooPaymentsIsActive === 'boolean'
						? wooPaymentsIsActive
						: false
				);
				setIsWooPaymentsInstalled(
					typeof wooPaymentsIsInstalled === 'boolean'
						? wooPaymentsIsInstalled
						: false
				);
			}
		};

		fetchWooPaymentsStatus();

		// Cleanup function to prevent state updates after unmount
		return () => {
			isMounted = false;
		};
	}, [] );

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
