/** @format */
/**
 * External dependencies
 */
import { Component } from '@wordpress/element';
import { filter } from 'lodash';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import { Stepper } from '@woocommerce/components';
import HeaderLogo from './header-logo';

export default class ProfileWizardHeader extends Component {
	renderStepper() {
		const steps = filter( this.props.steps, step => !! step.label );
		return <Stepper steps={ steps } currentStep={ this.props.currentStep } />;
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
