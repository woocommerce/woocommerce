/**
 * External dependencies
 */
import classnames from 'classnames';
import { createElement, Component, Fragment } from '@wordpress/element';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import Spinner from '../spinner';
import CheckIcon from './check-icon';

/**
 * A stepper component to indicate progress in a set number of steps.
 */
class Stepper extends Component {
	renderCurrentStepContent() {
		const { currentStep, steps } = this.props;
		const step = steps.find( ( s ) => currentStep === s.key );

		if ( ! step.content ) {
			return null;
		}

		return (
			<div className="woocommerce-stepper_content">{ step.content }</div>
		);
	}

	render() {
		const {
			className,
			currentStep,
			steps,
			isVertical,
			isPending,
		} = this.props;
		const currentIndex = steps.findIndex( ( s ) => currentStep === s.key );
		const stepperClassName = classnames( 'woocommerce-stepper', className, {
			'is-vertical': isVertical,
		} );

		return (
			<div className={ stepperClassName }>
				<div className="woocommerce-stepper__steps">
					{ steps.map( ( step, i ) => {
						const {
							key,
							label,
							description,
							isComplete,
							onClick,
						} = step;
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
												: null
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
										this.renderCurrentStepContent() }
								</div>
								{ ! isVertical && (
									<div className="woocommerce-stepper__step-divider" />
								) }
							</Fragment>
						);
					} ) }
				</div>

				{ ! isVertical && this.renderCurrentStepContent() }
			</div>
		);
	}
}

Stepper.propTypes = {
	/**
	 * Additional class name to style the component.
	 */
	className: PropTypes.string,
	/**
	 * The current step's key.
	 */
	currentStep: PropTypes.string.isRequired,
	/**
	 * An array of steps used.
	 */
	steps: PropTypes.arrayOf(
		PropTypes.shape( {
			/**
			 * Content displayed when the step is active.
			 */
			content: PropTypes.node,
			/**
			 * Description displayed beneath the label.
			 */
			description: PropTypes.oneOfType( [
				PropTypes.string,
				PropTypes.array,
			] ),
			/**
			 * Optionally mark a step complete regardless of step index.
			 */
			isComplete: PropTypes.bool,
			/**
			 * Key used to identify step.
			 */
			key: PropTypes.string.isRequired,
			/**
			 * Label displayed in stepper.
			 */
			label: PropTypes.string.isRequired,
			/**
			 * A function to be called when the step label is clicked.
			 */
			onClick: PropTypes.func,
		} )
	).isRequired,

	/**
	 * If the stepper is vertical instead of horizontal.
	 */
	isVertical: PropTypes.bool,

	/**
	 * Optionally mark the current step as pending to show a spinner.
	 */
	isPending: PropTypes.bool,
};

Stepper.defaultProps = {
	isVertical: false,
	isPending: false,
};

export default Stepper;
