/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import SidebarItem from './sidebar-item';
import { WooPaymentsProviderOnboardingStep } from '~/settings-payments/onboarding/types';
import { recordPaymentsOnboardingEvent } from '~/settings-payments/utils';

/**
 * Stepper component that renders only the active step from its children
 */
export default function Stepper( {
	activeTopLevelStep,
	activeSubStep,
	steps,
	justCompletedStepId,
	includeSidebar = false,
	sidebarTitle,
	context = {},
}: {
	/**
	 * The active top-level step key
	 */
	activeTopLevelStep: string;
	/**
	 * The active sub-step key
	 */
	activeSubStep: WooPaymentsProviderOnboardingStep | undefined;
	/**
	 * The ID of the step that was just completed.
	 * This can be used by steps to mark themselves as completed but moving to the next step depends on user interaction.
	 */
	justCompletedStepId?: string | null;
	/**
	 * The steps to render
	 */
	steps: WooPaymentsProviderOnboardingStep[];
	/**
	 * The title of the sidebar
	 */
	sidebarTitle?: string;
	/**
	 * Whether to include the sidebar
	 */
	includeSidebar?: boolean;
	/**
	 * Context for the stepper, including the session entry point.
	 */
	context?: {
		sessionEntryPoint?: string;
	};
} ): React.ReactNode {
	// Find the active step component
	const topLevelStep = steps.find(
		( step ) => step.id === activeTopLevelStep
	);

	// Track the step view.
	useEffect( () => {
		if ( activeSubStep ) {
			recordPaymentsOnboardingEvent(
				'woopayments_onboarding_modal_step_view',
				{
					step: activeSubStep.id,
					source: context?.sessionEntryPoint || 'unknown',
				}
			);
		}
	}, [ activeSubStep ] );

	if ( ! topLevelStep ) return null;

	const activeStepIndex =
		steps.findIndex( ( step ) => step.id === activeTopLevelStep ) + 1;

	// Helper function to determine if a step is completed
	const isStepCompleted = (
		step: WooPaymentsProviderOnboardingStep
	): boolean => {
		return (
			step.id === justCompletedStepId ||
			step.status === 'completed' ||
			activeStepIndex === steps.length
		);
	};

	// Sort steps to show completed ones first
	const sortedSteps = steps.sort( ( a, b ) => {
		const aCompleted = isStepCompleted( a );
		const bCompleted = isStepCompleted( b );

		if ( aCompleted === bCompleted ) {
			return 0;
		}
		return aCompleted ? -1 : 1;
	} );

	// Renders only the active step based on the current step ID.
	return (
		<>
			{ includeSidebar && (
				<div className="settings-payments-onboarding-modal__sidebar">
					<div className="settings-payments-onboarding-modal__sidebar--header">
						<h2 className="settings-payments-onboarding-modal__sidebar--header-title">
							{ sidebarTitle }
						</h2>
						<div className="settings-payments-onboarding-modal__sidebar--header-steps">
							{ /* translators: %1$s: current step number, %2$s: total number of steps */ }
							{ sprintf(
								/* translators: %1$s: current step number, %2$s: total number of steps */
								__( 'Step %1$s of %2$s', 'woocommerce' ),
								activeStepIndex,
								steps.length
							) }
						</div>
					</div>
					<div className="settings-payments-onboarding-modal__sidebar--list">
						{ sortedSteps.map( ( step ) => (
							<SidebarItem
								key={ step.id }
								label={ step.label }
								isCompleted={ isStepCompleted( step ) }
								isActive={ step.id === activeTopLevelStep }
							/>
						) ) }
					</div>
				</div>
			) }
			<div className="settings-payments-onboarding-modal__content">
				<div
					className="settings-payments-onboarding-modal__step"
					id={ activeSubStep?.id }
				>
					{ activeSubStep?.content }
				</div>
			</div>
		</>
	);
}
