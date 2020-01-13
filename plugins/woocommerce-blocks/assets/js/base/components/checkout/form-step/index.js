/**
 * External dependencies
 */
import classnames from 'classnames';
import PropTypes from 'prop-types';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Label from '../../label';
import './style.scss';

const StepNumber = ( { stepNumber } ) => {
	return (
		<div className="wc-block-checkout-step__number">
			<Label
				label={ stepNumber }
				screenReaderLabel={ sprintf(
					__(
						// translators: %s is a step number (1, 2, 3...)
						'Step %d',
						'woo-gutenberg-products-block'
					),
					stepNumber
				) }
			/>
		</div>
	);
};

const StepHeading = ( { title, stepHeadingContent } ) => (
	<div className="wc-block-checkout-step__heading">
		<h4 aria-hidden="true" className="wc-block-checkout-step__title">
			{ title }
		</h4>
		<span className="wc-block-checkout-step__heading-content">
			{ stepHeadingContent }
		</span>
	</div>
);

const FormStep = ( {
	id,
	className,
	stepNumber,
	title,
	legend,
	description,
	children,
	stepHeadingContent = () => null,
} ) => {
	return (
		<fieldset
			className={ classnames( className, 'wc-block-checkout-step' ) }
			id={ id }
		>
			<legend className="screen-reader-text">{ legend || title }</legend>
			<StepNumber stepNumber={ stepNumber } />
			<StepHeading
				title={ title }
				stepHeadingContent={ stepHeadingContent() }
			/>
			<span className="wc-block-checkout-step__description">
				{ description }
			</span>
			<div className="wc-block-checkout-step__content">{ children }</div>
		</fieldset>
	);
};

FormStep.propTypes = {
	id: PropTypes.string,
	className: PropTypes.string,
	stepNumber: PropTypes.number,
	title: PropTypes.string,
	description: PropTypes.string,
	children: PropTypes.node,
	stepHeadingContent: PropTypes.func,
};

export default FormStep;
