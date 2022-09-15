/**
 * External dependencies
 */
import classnames from 'classnames';
import { createElement, Fragment } from '@wordpress/element';
import type React from 'react';

/**
 * Internal dependencies
 */
import Spinner from '../spinner';
import CheckIcon from './check-icon';

export interface StepperProps {
	/** Additional class name to style the component. */
	className?: string;
	/** The current step's key. */
	currentStep: string;
	/** An array of steps used. */
	steps: Array< {
		/** Content displayed when the step is active. */
		content: React.ReactNode;
		/** Description displayed beneath the label. */
		description: string | Array< string >;
		/** Optionally mark a step complete regardless of step index. */
		isComplete?: boolean;
		/** Key used to identify step. */
		key: string;
		/** Label displayed in stepper. */
		label: string;
		/** A function to be called when the step label is clicked. */
		onClick?: ( key: string ) => void;
	} >;
	/** If the stepper is vertical instead of horizontal. */
	isVertical?: boolean;
	/**  Optionally mark the current step as pending to show a spinner. */
	isPending?: boolean;
}

/**
 * A stepper component to indicate progress in a set number of steps.
 */
export const Stepper: React.FC< StepperProps > = ( {
	className,
	currentStep,
	steps,
	isVertical = false,
	isPending = false,
}: StepperProps ) => {
	const renderCurrentStepContent = () => {
		const step = steps.find( ( s ) => currentStep === s.key );

		if ( ! step || ! step.content ) {
			return null;
		}

		return (
			<div className="woocommerce-stepper_content">{ step.content }</div>
		);
	};

	const currentIndex = steps.findIndex( ( s ) => currentStep === s.key );
	const stepperClassName = classnames( 'woocommerce-stepper', className, {
		'is-vertical': isVertical,
	} );

	return (
		<div className={ stepperClassName }>
			<div className="woocommerce-stepper__steps">
				{ steps.map( ( step, i ) => {
					const { key, label, description, isComplete, onClick } =
						step;
					const isCurrentStep = key === currentStep;
					const stepClassName = classnames(
						'woocommerce-stepper__step',
						{
							'is-active': isCurrentStep,
							'is-complete':
								typeof isComplete !== 'undefined'
									? isComplete
									: currentIndex > i,
						}
					);
					const icon =
						isCurrentStep && isPending ? (
							<Spinner />
						) : (
							<div className="woocommerce-stepper__step-icon">
								<span className="woocommerce-stepper__step-number">
									{ i + 1 }
								</span>
								<CheckIcon />
							</div>
						);

					const LabelWrapper =
						typeof onClick === 'function' ? 'button' : 'div';
					return (
						<Fragment key={ key }>
							<div className={ stepClassName }>
								<LabelWrapper
									className="woocommerce-stepper__step-label-wrapper"
									onClick={
										typeof onClick === 'function'
											? () => onClick( key )
											: undefined
									}
								>
									{ icon }
									<div className="woocommerce-stepper__step-text">
										<span className="woocommerce-stepper__step-label">
											{ label }
										</span>
										{ description && (
											<span className="woocommerce-stepper__step-description">
												{ description }
											</span>
										) }
									</div>
								</LabelWrapper>
								{ isCurrentStep &&
									isVertical &&
									renderCurrentStepContent() }
							</div>
							{ ! isVertical && (
								<div className="woocommerce-stepper__step-divider" />
							) }
						</Fragment>
					);
				} ) }
			</div>

			{ ! isVertical && renderCurrentStepContent() }
		</div>
	);
};

export default Stepper;
