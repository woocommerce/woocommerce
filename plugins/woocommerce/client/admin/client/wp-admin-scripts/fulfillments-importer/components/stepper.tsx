/**
 * External dependencies
 */
import React from 'react';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import type { ImporterStep } from '../hooks/use-importer-state';

interface StepDescriptor {
	id: ImporterStep;
	label: string;
}

interface StepperProps {
	currentStep: ImporterStep;
}

const STEPS: StepDescriptor[] = [
	{ id: 'upload', label: __( 'Upload', 'woocommerce' ) },
	{ id: 'mapping', label: __( 'Mapping', 'woocommerce' ) },
	{ id: 'import', label: __( 'Import', 'woocommerce' ) },
	{ id: 'done', label: __( 'Done', 'woocommerce' ) },
];

function statusFor(
	stepId: ImporterStep,
	currentStep: ImporterStep
): 'completed' | 'current' | 'upcoming' {
	const currentIndex = STEPS.findIndex( ( s ) => s.id === currentStep );
	const stepIndex = STEPS.findIndex( ( s ) => s.id === stepId );
	if ( stepIndex < currentIndex ) {
		return 'completed';
	}
	if ( stepIndex === currentIndex ) {
		return 'current';
	}
	return 'upcoming';
}

const Stepper: React.FC< StepperProps > = ( { currentStep } ) => (
	<ol
		className="woocommerce-fulfillment-importer-stepper"
		aria-label={ __( 'Import progress', 'woocommerce' ) }
	>
		{ STEPS.map( ( step, index ) => {
			const status = statusFor( step.id, currentStep );
			return (
				<li
					key={ step.id }
					className={ `woocommerce-fulfillment-importer-stepper__step is-${ status }` }
					aria-current={ status === 'current' ? 'step' : undefined }
				>
					<span className="woocommerce-fulfillment-importer-stepper__bullet">
						{ index + 1 }
					</span>
					<span className="woocommerce-fulfillment-importer-stepper__label">
						{ step.label }
					</span>
				</li>
			);
		} ) }
	</ol>
);

export default Stepper;
export { STEPS };
