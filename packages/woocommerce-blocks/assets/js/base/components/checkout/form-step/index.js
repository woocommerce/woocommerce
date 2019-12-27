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
		<div className="wc-components-checkout-step__number">
			<Label
				label={ stepNumber }
				screenReaderLabel={ sprintf(
					__(
						// translators: %s is a step number (1, 2, 3...)
						'Step %d',
						'woocommerce'
					),
					stepNumber
				) }
			/>
		</div>
	);
};

const StepHeading = ( { title, stepHeadingContent } ) => (
	<div className="wc-components-checkout-step__heading">
		<h4 className="wc-components-checkout-step__title">{ title }</h4>
		<span className="wc-components-checkout-step__heading-content">
			{ stepHeadingContent }
		</span>
	</div>
);

const FormStep = ( {
	id,
	className,
	stepNumber,
	title,
	description,
	children,
	stepHeadingContent = () => null,
} ) => {
	return (
		<div
			className={ classnames( className, 'wc-components-checkout-step' ) }
			id={ id }
		>
			<StepNumber stepNumber={ stepNumber } />
			<StepHeading
				title={ title }
				stepHeadingContent={ stepHeadingContent() }
			/>
			<span className="wc-components-checkout-step__description">
				{ description }
			</span>
			<div className="wc-components-checkout-step__content">
				{ children }
			</div>
		</div>
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
