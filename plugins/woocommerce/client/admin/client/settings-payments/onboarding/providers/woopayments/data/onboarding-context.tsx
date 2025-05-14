/**
 * External dependencies
 */
import {
	createContext,
	useContext,
	useCallback,
	useState,
	useEffect,
} from '@wordpress/element';
import { useDispatch, useSelect } from '@wordpress/data';
import {
	woopaymentsOnboardingStore,
	WooPaymentsOnboardingStepContent,
	paymentSettingsStore,
} from '@woocommerce/data';
import { getHistory, getNewPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import {
	WooPaymentsProviderOnboardingStep,
	OnboardingContextType,
} from '~/settings-payments/onboarding/types';
import { steps as woopaymentsSteps } from '../steps';

/**
 * Context to manage onboarding steps
 */
const OnboardingContext = createContext< OnboardingContextType >( {
	steps: [],
	isLoading: true,
	currentStep: undefined,
	context: {},
	navigateToStep: () => undefined,
	navigateToNextStep: () => undefined,
	getStepByKey: () => undefined,
	refreshStoreData: () => undefined,
	closeModal: () => undefined,
} );

export const useOnboardingContext = () => useContext( OnboardingContext );

export const OnboardingProvider: React.FC< {
	children: React.ReactNode;
	closeModal: () => void;
} > = ( { children, closeModal } ) => {
	const history = getHistory();

	// Use React state to manage steps and loading state
	const [ stateStoreSteps, setStateStoreSteps ] = useState<
		WooPaymentsOnboardingStepContent[]
	>( [] );
	const [ isStateStoreLoading, setIsStateStoreLoading ] = useState( true );
	const [ allSteps, setAllSteps ] = useState<
		WooPaymentsProviderOnboardingStep[]
	>( [] );

	const {
		invalidateResolutionForStoreSelector: invalidateWooPaymentsOnboarding,
	} = useDispatch( woopaymentsOnboardingStore );

	const { invalidateResolutionForStoreSelector: invalidatePaymentProviders } =
		useDispatch( paymentSettingsStore );

	// Initial data fetch from store
	const { storeData, isStoreLoading } = useSelect(
		( select ) => ( {
			storeData: select( woopaymentsOnboardingStore ).getOnboardingData(),
			isStoreLoading: select(
				woopaymentsOnboardingStore
			).isOnboardingDataRequestPending(),
		} ),
		[]
	);

	/**
	 * Helper functions
	 */
	const getStepByKey = useCallback(
		( stepKey: string ) => {
			return allSteps.find( ( step ) => step.id === stepKey );
		},
		[ allSteps ]
	);

	// Helper function to check if all dependencies of a step are completed
	const areStepDependenciesCompleted = useCallback(
		(
			step: WooPaymentsProviderOnboardingStep,
			steps: WooPaymentsProviderOnboardingStep[]
		) => {
			if ( ! step.dependencies || step.dependencies.length === 0 ) {
				return true;
			}

			return step.dependencies.every( ( dependencyId ) => {
				const dependencyStep = steps.find(
					( s ) => s.id === dependencyId
				);
				return dependencyStep?.status === 'completed';
			} );
		},
		[]
	);
	// Navigation helper
	const navigateToStep = useCallback(
		( stepKey: string ) => {
			const step = getStepByKey( stepKey );
			if ( step?.path ) {
				const newPath = getNewPath( { path: step.path }, step.path, {
					page: 'wc-settings',
					tab: 'checkout',
				} );
				history.push( newPath );
			}
		},
		[ getStepByKey, history ]
	);

	// Find the first incomplete step with completed dependencies
	const currentStep = allSteps.find(
		( step ) =>
			step.status !== 'completed' &&
			areStepDependenciesCompleted( step, allSteps )
	);

	const navigateToNextStep = useCallback( () => {
		const currentStepIndex = allSteps.findIndex(
			( step ) => step.id === currentStep?.id
		);
		if ( currentStepIndex !== -1 ) {
			// Mark current step as completed
			if ( currentStep?.status !== 'completed' ) {
				// Change step completion status in allSteps
				setAllSteps(
					allSteps.map( ( step ) =>
						step.id === currentStep?.id
							? { ...step, status: 'completed' as const }
							: step
					)
				);
			}

			// Find the next step that is not completed and has completed dependencies
			const nextStep = allSteps.find(
				( step ) =>
					step.status !== 'completed' &&
					areStepDependenciesCompleted( step, allSteps )
			);

			if ( nextStep ) {
				navigateToStep( nextStep.id );
			}
		}
	}, [
		currentStep,
		allSteps,
		navigateToStep,
		areStepDependenciesCompleted,
	] );

	const resetLocalState = () => {
		setStateStoreSteps( [] );
		setIsStateStoreLoading( true );
		setAllSteps( [] );
	};

	const refreshStoreData = () => {
		// Reset the onboarding data both in the store and local state when the onboardingcontext mounts.
		// This is important to ensure that the onboarding data is cleared when the modal is closed.
		// This is to avoid stale data when the modal is opened again.
		resetLocalState();
		invalidateWooPaymentsOnboarding( 'getOnboardingData' );
	};

	/**
	 * useEffect functions
	 */

	// Update local state when store data changes
	useEffect( () => {
		if ( ! isStoreLoading && storeData.steps.length > 0 ) {
			setStateStoreSteps( storeData.steps );
			setIsStateStoreLoading( false );
		}
	}, [ storeData, isStoreLoading ] );

	// Update all steps when stateStoreSteps changes
	useEffect( () => {
		const mapWooPaymentsSteps = woopaymentsSteps
			// First, filter out steps that are not returned from the API.
			// This is to avoid showing steps that are not useful to the user.
			.filter( ( step ) => {
				if ( step.type === 'backend' ) {
					return (
						stateStoreSteps.findIndex(
							( s ) => s.id === step.id
						) !== -1
					);
				}
				return true;
			} )
			.map( ( step ) => {
				// If step type is backend, add the status, path and dependencies from the store
				if ( step.type === 'backend' ) {
					const backendStep = stateStoreSteps.find(
						( s ) => s.id === step.id
					);

					return Object.assign( {}, step, {
						status: backendStep?.status || 'not_started',
						dependencies: backendStep?.dependencies || [],
						path: backendStep?.path,
						context: backendStep?.context,
						actions: backendStep?.actions,
					} );
				}

				// For frontend steps, create a base step object first
				return Object.assign( {}, step );
			} );

		// Now determine dependencies status in a second pass to avoid stale data
		const stepsWithDependenciesResolved = mapWooPaymentsSteps.map(
			( step ) => {
				if ( step.type === 'frontend' ) {
					return {
						...step,
						status: areStepDependenciesCompleted(
							step,
							mapWooPaymentsSteps
						)
							? ( 'completed' as const )
							: ( 'not_started' as const ),
					};
				}
				return step;
			}
		);

		setAllSteps(
			stepsWithDependenciesResolved as WooPaymentsProviderOnboardingStep[]
		);
	}, [ stateStoreSteps, areStepDependenciesCompleted ] );

	useEffect( () => {
		// Invalidate the getOnboardingData store selector to ensure the latest data is fetched.
		refreshStoreData();
	}, [] );

	return (
		<OnboardingContext.Provider
			value={ {
				steps: allSteps,
				context: storeData.context,
				isLoading: isStateStoreLoading,
				currentStep,
				navigateToStep,
				navigateToNextStep,
				getStepByKey,
				refreshStoreData,
				closeModal: () => {
					closeModal();

					// Invalidate the getPaymentProviders store selector to ensure the latest data is fetched.
					// This is important to ensure that the payment providers buttons are up to date.
					invalidatePaymentProviders( 'getPaymentProviders' );
				},
			} }
		>
			{ children }
		</OnboardingContext.Provider>
	);
};
