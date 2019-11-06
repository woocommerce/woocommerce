/** @format */
/**
 * External dependencies
 */
import { Component } from '@wordpress/element';
import { filter } from 'lodash';
import classnames from 'classnames';

/**
 * WooCommerce dependencies
 */
import { updateQueryString } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { Stepper } from '@woocommerce/components';
import HeaderLogo from './header-logo';

export default class ProfileWizardHeader extends Component {
	renderStepper() {
		const { currentStep, steps } = this.props;
		const visibleSteps = filter( steps, step => !! step.label );
		const currentStepIndex = visibleSteps.findIndex( step => step.key === currentStep );

		visibleSteps.map( ( step, index ) => {
			const previousStep = visibleSteps[ index - 1 ];

			if ( index < currentStepIndex ) {
				step.isComplete = true;
			}

			if ( ! previousStep || previousStep.isComplete ) {
				step.onClick = key => updateQueryString( { step: key } );
			}
			return step;
		} );

		return <Stepper steps={ visibleSteps } currentStep={ currentStep } />;
	}

	render() {
		const currentStep = this.props.steps.find( s => s.key === this.props.currentStep );
		const showStepper = ! currentStep || ! currentStep.label ? false : true;
		const classes = classnames( 'woocommerce-profile-wizard__header', {
			'is-stepper': showStepper,
		} );
		return <div className={ classes }>{ showStepper ? this.renderStepper() : <HeaderLogo /> }</div>;
	}
}
