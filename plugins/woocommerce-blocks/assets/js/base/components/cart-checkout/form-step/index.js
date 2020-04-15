/**
 * External dependencies
 */
import classnames from 'classnames';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import './style.scss';

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
	title,
	legend,
	description,
	children,
	disabled = false,
	stepHeadingContent = () => {},
} ) => {
	return (
		<fieldset
			className={ classnames( className, 'wc-block-checkout-step' ) }
			id={ id }
			disabled={ disabled }
		>
			<legend className="screen-reader-text">{ legend || title }</legend>
			<StepHeading
				title={ title }
				stepHeadingContent={ stepHeadingContent() }
			/>
			{ !! description && (
				<span className="wc-block-checkout-step__description">
					{ description }
				</span>
			) }
			<div className="wc-block-checkout-step__content">{ children }</div>
		</fieldset>
	);
};

FormStep.propTypes = {
	id: PropTypes.string,
	className: PropTypes.string,
	title: PropTypes.string,
	description: PropTypes.string,
	children: PropTypes.node,
	stepHeadingContent: PropTypes.func,
	disabled: PropTypes.bool,
	legend: PropTypes.string,
};

export default FormStep;
