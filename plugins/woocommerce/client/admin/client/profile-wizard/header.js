/**
 * External dependencies
 */
import { Component } from '@wordpress/element';
import { filter, isEqual } from 'lodash';
import { Stepper } from '@woocommerce/components';
import { updateQueryString } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import UnsavedChangesModal from './unsaved-changes-modal';

export default class ProfileWizardHeader extends Component {
	constructor( props ) {
		super( props );
		this.state = {
			showUnsavedChangesModal: false,
		};
		this.lastClickedStepKey = null;
		this.onStepClick = this.onStepClick.bind( this );
	}

	shouldWarnForUnsavedChanges( step ) {
		if ( typeof this.props.stepValueChanges[ step ] !== 'undefined' ) {
			const initialValues =
				this.props.stepValueChanges[ step ].initialValues;
			const currentValues =
				this.props.stepValueChanges[ step ].currentValues;

			if (
				Array.isArray( initialValues ) &&
				Array.isArray( currentValues )
			) {
				initialValues.sort();
				currentValues.sort();
			}

			return ! isEqual( initialValues, currentValues );
		}
		return false;
	}

	findCurrentStep() {
		return this.props.steps.find(
			( s ) => s.key === this.props.currentStep
		);
	}

	moveToLastClickedStep() {
		if ( this.lastClickedStepKey ) {
			updateQueryString( { step: this.lastClickedStepKey } );
			this.lastClickedStepKey = null;
		}
	}

	saveCurrentStepChanges() {
		const currentStep = this.findCurrentStep();
		if ( ! currentStep ) {
			return null;
		}
		const stepValueChanges = this.props.stepValueChanges[ currentStep.key ];
		if ( typeof stepValueChanges.onSave === 'function' ) {
			stepValueChanges.onSave();
		}
	}

	renderStepper() {
		const { currentStep, steps } = this.props;
		const visibleSteps = filter( steps, ( step ) => !! step.label );
		const currentStepIndex = visibleSteps.findIndex(
			( step ) => step.key === currentStep
		);

		visibleSteps.forEach( ( step, index ) => {
			const previousStep = visibleSteps[ index - 1 ];
			step.isComplete = step.isComplete || index < currentStepIndex;

			const canClickStepLabel =
				! previousStep || previousStep.isComplete || step.isComplete;

			if ( canClickStepLabel ) {
				step.onClick = this.onStepClick;
			}
		} );
		return <Stepper steps={ visibleSteps } currentStep={ currentStep } />;
	}

	onStepClick( key ) {
		const { currentStep } = this.props;
		if ( this.shouldWarnForUnsavedChanges( currentStep ) ) {
			this.setState( { showUnsavedChangesModal: true } );
			this.lastClickedStepKey = key;
		} else {
			updateQueryString( { step: key } );
		}
	}

	render() {
		const currentStep = this.findCurrentStep();

		if ( ! currentStep || ! currentStep.label ) {
			return null;
		}

		return (
			<div className="woocommerce-profile-wizard__header">
				{ this.state.showUnsavedChangesModal && (
					<UnsavedChangesModal
						onClose={ () => {
							this.setState( { showUnsavedChangesModal: false } );
							this.moveToLastClickedStep();
						} }
						onSave={ () => {
							this.saveCurrentStepChanges();
							this.setState( { showUnsavedChangesModal: false } );
							this.moveToLastClickedStep();
						} }
					/>
				) }
				{ this.renderStepper() }
			</div>
		);
	}
}
