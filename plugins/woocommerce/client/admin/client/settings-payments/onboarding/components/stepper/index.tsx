/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import SidebarItem from './sidebar-item';
import { WooPaymentsProviderOnboardingStep } from '~/settings-payments/onboarding/types';

/**
 * Stepper component that renders only the active step from its children
 */
export default function Stepper( {
	active,
	steps,
	justCompletedStepId,
	includeSidebar = false,
	sidebarTitle,
}: {
	/**
	 * The active step key
	 */
	active: string;
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
} ): React.ReactNode {
	// Find the active step component
	const activeStep = steps.find( ( step ) => step.id === active );

	if ( ! activeStep ) return null;

	const activeStepIndex =
		steps.findIndex( ( step ) => step.id === active ) + 1;

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
						{ steps.map( ( step ) => (
							<SidebarItem
								key={ step.id }
								label={ step.label }
								isCompleted={
									step.id === justCompletedStepId ||
									step.status === 'completed' ||
									activeStepIndex === steps.length
								}
								isActive={ step.id === active }
							/>
						) ) }
					</div>
				</div>
			) }
			<div className="settings-payments-onboarding-modal__content">
				<div
					className="settings-payments-onboarding-modal__step"
					id={ activeStep.id }
				>
					{ activeStep.content }
				</div>
			</div>
		</>
	);
}
