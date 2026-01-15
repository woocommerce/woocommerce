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
import { getHistory, getNewPath, getQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import {
	WooPaymentsProviderOnboardingStep,
	OnboardingContextType,
} from '~/settings-payments/onboarding/types';
import { wooPaymentsOnboardingSessionEntrySettings } from '~/settings-payments/constants';

/**
 * Recursively search for a step by key in a nested structure.
 */
const getStepByKeyRecursively = (
	key: string,
	steps: WooPaymentsProviderOnboardingStep[]
): WooPaymentsProviderOnboardingStep | undefined => {
	for ( const step of steps ) {
		if ( step.id === key ) {
			return step;
		}
		if ( step.subSteps ) {
			const found = getStepByKeyRecursively( key, step.subSteps );
			if ( found ) {
				return found;
			}
		}
	}
	return undefined;
};

/**
 * URL Strategy interface for handling navigation in different contexts
 */
interface URLStrategy {
	buildStepURL: (
		stepPath: string,
		currentParams?: Record< string, string >
	) => string;
	preserveParams?: string[]; // params to preserve when navigating.
}

/**
 * Default URL strategy for settings-payments (backward compatibility)
 */
const defaultURLStrategy: URLStrategy = {
	buildStepURL: ( stepPath: string ) => {
		return getNewPath( { path: stepPath }, stepPath, {
			page: 'wc-settings',
			tab: 'checkout',
		} );
	},
	preserveParams: [ 'source', 'from' ], // params to preserve when navigating.
};

/**
 * Context to manage onboarding steps
 */
const OnboardingContext = createContext< OnboardingContextType >( {
	steps: [],
	isLoading: true,
	currentStep: undefined,
	currentTopLevelStep: undefined,
	context: {},
	navigateToStep: () => undefined,
	navigateToNextStep: () => undefined,
	getStepByKey: () => undefined,
	refreshStoreData: () => undefined,
	closeModal: () => undefined,
	justCompletedStepId: null,
	setJustCompletedStepId: () => undefined,
	sessionEntryPoint: '',
	snackbar: {
		show: false,
		duration: 4000,
		message: '',
	},
	setSnackbar: () => undefined,
} );

export const useOnboardingContext = () => useContext( OnboardingContext );

export const OnboardingProvider: React.FC< {
	children: React.ReactNode;
	onboardingSteps: WooPaymentsProviderOnboardingStep[];
	closeModal: () => void;
	onFinish?: () => void;
	urlStrategy?: URLStrategy;
	sessionEntryPoint?: string;
} > = ( {
	children,
	onboardingSteps,
	closeModal,
	onFinish,
	urlStrategy,
	sessionEntryPoint = wooPaymentsOnboardingSessionEntrySettings,
} ) => {
	const history = getHistory();

	// Use React state to manage steps and loading state
	const [ stateStoreSteps, setStateStoreSteps ] = useState<
		WooPaymentsOnboardingStepContent[]
	>( [] );
	const [ isStateStoreLoading, setIsStateStoreLoading ] = useState( true );
	const [ allSteps, setAllSteps ] = useState<
		WooPaymentsProviderOnboardingStep[]
	>( [] );

	// New state for tracking just completed step
	const [ justCompletedStepId, setStepId ] = useState< string | null >(
		null
	);

	const [ snackbar, setSnackbar ] = useState< {
		show: boolean;
		message: string;
		duration?: number;
		className?: string;
	} >( {
		show: false,
		duration: 4000,
		message: '',
	} );

	const setJustCompletedStepId = useCallback( ( stepId: string | null ) => {
		setStepId( stepId );
	}, [] );

	const {
		invalidateResolutionForStoreSelector: invalidateWooPaymentsOnboarding,
	} = useDispatch( woopaymentsOnboardingStore );

	const { invalidateResolutionForStoreSelector: invalidatePaymentProviders } =
		useDispatch( paymentSettingsStore );

	// Initial data fetch from store with source parameter
	const { storeData, isStoreLoading } = useSelect(
		( select ) => ( {
			storeData: select( woopaymentsOnboardingStore ).getOnboardingData(
				sessionEntryPoint
			),
			isStoreLoading: select(
				woopaymentsOnboardingStore
			).isOnboardingDataRequestPending(),
		} ),
		[ sessionEntryPoint ]
	);

	/**
	 * Helper functions
	 */
	const getStepByKey = useCallback(
		(
			stepKey: string,
			steps: WooPaymentsProviderOnboardingStep[] = allSteps
		): WooPaymentsProviderOnboardingStep | undefined => {
			return getStepByKeyRecursively( stepKey, steps );
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
				const dependencyStep = getStepByKeyRecursively(
					dependencyId,
					steps
				);
				return dependencyStep?.status === 'completed';
			} );
		},
		[]
	);

	// Helper function to determine if a frontend step should be completed
	const isFrontendStepCompleted = useCallback(
		(
			step: WooPaymentsProviderOnboardingStep,
			parentStep?: WooPaymentsProviderOnboardingStep,
			nextSibling?: WooPaymentsProviderOnboardingStep
		): boolean | undefined => {
			if ( step.type !== 'frontend' ) {
				return step.status === 'completed';
			}

			if ( parentStep ) {
				// Rule 2: A frontend sub-step is completed if its parent is, or if the next backend sibling is active/done.
				return (
					parentStep.status === 'completed' ||
					( nextSibling &&
						nextSibling.type === 'backend' &&
						( nextSibling.status === 'in_progress' ||
							nextSibling.status === 'completed' ) )
				);
			} else if ( step.subSteps?.length ) {
				// Rule 1: A frontend top-level step is completed if all its sub-steps are.
				return step.subSteps.every( ( s ) =>
					s.status ? s.status === 'completed' : false
				);
			}

			return false;
		},
		[]
	);
	// Navigation helper
	const navigateToStep = useCallback(
		( stepKey: string ) => {
			const step = getStepByKey( stepKey );
			if ( step?.path ) {
				// Use provided urlStrategy or fall back to default
				const strategy = urlStrategy || defaultURLStrategy;

				// Get current query params if strategy wants to preserve some
				const currentParams = strategy.preserveParams
					? ( getQuery() as Record< string, string > )
					: {};
				const preservedParams =
					strategy.preserveParams?.reduce(
						( acc: Record< string, string >, param: string ) => {
							if ( currentParams[ param ] ) {
								acc[ param ] = currentParams[ param ];
							}
							return acc;
						},
						{} as Record< string, string >
					) || {};

				const newPath = strategy.buildStepURL(
					step.path,
					preservedParams
				);
				history.push( newPath );
			}
		},
		[ getStepByKey, history, urlStrategy ]
	);

	const findFirstIncompleteStep = useCallback(
		(
			steps: WooPaymentsProviderOnboardingStep[],
			allStepsCollection: WooPaymentsProviderOnboardingStep[],
			parentStep?: WooPaymentsProviderOnboardingStep
		): WooPaymentsProviderOnboardingStep | undefined => {
			for ( const [ index, step ] of steps.entries() ) {
				// Special completion check for frontend steps.
				if ( step.type === 'frontend' ) {
					const nextSubStep = steps[ index + 1 ];
					const isCompleted = isFrontendStepCompleted(
						step,
						parentStep,
						nextSubStep
					);

					if ( isCompleted ) {
						continue; // Skip this step, it's considered done for navigation.
					}
				}

				if (
					step.status !== 'completed' &&
					areStepDependenciesCompleted( step, allStepsCollection )
				) {
					// If the step has sub-steps, check them first.
					if ( step.subSteps && step.subSteps.length > 0 ) {
						const incompleteSubStep = findFirstIncompleteStep(
							step.subSteps,
							allStepsCollection,
							step
						);
						if ( incompleteSubStep ) {
							return incompleteSubStep;
						}
					}
					// If no incomplete sub-step, this is the one.
					return step;
				}
			}
			return undefined;
		},
		[ areStepDependenciesCompleted ]
	);

	// Find the first incomplete step with completed dependencies
	const currentStep = findFirstIncompleteStep( allSteps, allSteps );

	const findTopLevelParent = (
		active: WooPaymentsProviderOnboardingStep | undefined
	): WooPaymentsProviderOnboardingStep | undefined => {
		if ( ! active ) {
			return undefined;
		}

		for ( const topStep of allSteps ) {
			if ( topStep.id === active.id ) {
				return topStep; // It's a top-level step
			}
			if ( topStep.subSteps?.some( ( sub ) => sub.id === active.id ) ) {
				return topStep; // It's a direct sub-step of a top-level step
			}
		}
		// This case shouldn't be reached with a well-formed step tree,
		// but as a fallback, return the active step itself.
		return active;
	};

	const currentTopLevelStep = findTopLevelParent( currentStep );

	const navigateToNextStep = useCallback( () => {
		if ( ! currentStep ) {
			onFinish?.();
			return;
		}

		const markStepCompleted = (
			steps: WooPaymentsProviderOnboardingStep[],
			stepId: string
		): WooPaymentsProviderOnboardingStep[] => {
			return steps.map( ( step ) => {
				if ( step.id === stepId ) {
					return { ...step, status: 'completed' as const };
				}
				if ( step.subSteps ) {
					const newSubSteps = markStepCompleted(
						step.subSteps,
						stepId
					);
					if ( newSubSteps !== step.subSteps ) {
						const allSubstepsCompleted = newSubSteps.every(
							( s ) => s.status === 'completed'
						);
						return {
							...step,
							subSteps: newSubSteps,
							status: allSubstepsCompleted
								? ( 'completed' as const )
								: step.status,
						};
					}
				}
				return step;
			} );
		};

		const newSteps = markStepCompleted( allSteps, currentStep.id );

		const nextStep = findFirstIncompleteStep( newSteps, newSteps );

		setAllSteps( newSteps as WooPaymentsProviderOnboardingStep[] );

		if ( nextStep ) {
			navigateToStep( nextStep.id );
		} else {
			onFinish?.();
		}
	}, [
		currentStep,
		allSteps,
		navigateToStep,
		findFirstIncompleteStep,
		onFinish,
	] );

	const resetLocalState = () => {
		setStateStoreSteps( [] );
		setIsStateStoreLoading( true );
		setJustCompletedStepId( null );
		setAllSteps( [] );
		setSnackbar( { show: false, message: '' } );
	};

	const refreshStoreData = () => {
		// Reset the onboarding data both in the store and local state when the onboarding context mounts.
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
		const mapWooPaymentsSteps = (
			stepsToMap: WooPaymentsProviderOnboardingStep[]
		): WooPaymentsProviderOnboardingStep[] => {
			return stepsToMap
				.map( ( step ) => {
					let mappedStep = { ...step };

					// If step type is backend, add the status, path and dependencies from the store
					if ( mappedStep.type === 'backend' ) {
						const backendStep = stateStoreSteps.find(
							( s ) => s.id === mappedStep.id
						);

						if ( ! backendStep ) {
							return null;
						}

						const backendStepWithErrors =
							backendStep as WooPaymentsOnboardingStepContent & {
								errors: [];
							};

						mappedStep = {
							...mappedStep,
							status:
								( backendStepWithErrors.status === 'started'
									? 'in_progress'
									: backendStepWithErrors.status ) ||
								'not_started',
							dependencies:
								backendStepWithErrors.dependencies || [],
							path: backendStepWithErrors.path,
							context: {
								...( mappedStep.context || {} ),
								...( backendStepWithErrors.context || {} ),
							} as WooPaymentsProviderOnboardingStep[ 'context' ],
							actions: backendStepWithErrors.actions,
							errors: backendStepWithErrors.errors,
						};
					}

					// Recursively map sub-steps
					if ( mappedStep.subSteps ) {
						mappedStep.subSteps = mapWooPaymentsSteps(
							mappedStep.subSteps
						);
					}

					return mappedStep;
				} )
				.filter(
					( step ): step is WooPaymentsProviderOnboardingStep =>
						step !== null
				);
		};

		const mappedSteps = mapWooPaymentsSteps( onboardingSteps );

		// Now determine dependencies status in a second pass to avoid stale data
		const resolveFrontendDependencies = (
			stepsToResolve: WooPaymentsProviderOnboardingStep[],
			allMappedSteps: WooPaymentsProviderOnboardingStep[],
			parentStep?: WooPaymentsProviderOnboardingStep
		): WooPaymentsProviderOnboardingStep[] => {
			return stepsToResolve.map( ( step, index ) => {
				const resolvedStep = { ...step };

				if ( resolvedStep.type === 'frontend' ) {
					// Handle sub-steps first if they exist
					if ( resolvedStep.subSteps?.length ) {
						resolvedStep.subSteps = resolveFrontendDependencies(
							resolvedStep.subSteps,
							allMappedSteps,
							resolvedStep
						);
					}

					// Apply the completion logic using the helper function
					const nextSubStep = stepsToResolve[ index + 1 ];
					const isCompleted = isFrontendStepCompleted(
						resolvedStep,
						parentStep,
						nextSubStep
					);

					resolvedStep.status = isCompleted
						? 'completed'
						: 'not_started';
				}

				if (
					resolvedStep.subSteps &&
					resolvedStep.type !== 'frontend'
				) {
					resolvedStep.subSteps = resolveFrontendDependencies(
						resolvedStep.subSteps,
						allMappedSteps,
						resolvedStep
					);
				}
				return resolvedStep;
			} );
		};

		const stepsWithDependenciesResolved = resolveFrontendDependencies(
			mappedSteps,
			mappedSteps
		);

		setAllSteps(
			stepsWithDependenciesResolved as WooPaymentsProviderOnboardingStep[]
		);
	}, [ stateStoreSteps, areStepDependenciesCompleted, onboardingSteps ] );

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
				currentTopLevelStep,
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
				justCompletedStepId,
				setJustCompletedStepId,
				sessionEntryPoint,
				snackbar,
				setSnackbar,
			} }
		>
			{ children }
		</OnboardingContext.Provider>
	);
};
