/**
 * External dependencies
 */
import classnames from 'classnames';
import PropTypes from 'prop-types';
import Title from '@woocommerce/base-components/title';

/**
 * Internal dependencies
 */
import './style.scss';

const StepHeading = ( { title, stepHeadingContent } ) => (
	<div className="wc-block-components-checkout-step__heading">
		<Title
			aria-hidden="true"
			className="wc-block-components-checkout-step__title"
			headingLevel="2"
		>
			{ title }
		</Title>
		{ !! stepHeadingContent && (
			<span className="wc-block-components-checkout-step__heading-content">
				{ stepHeadingContent }
			</span>
		) }
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
	showStepNumber = true,
	stepHeadingContent = () => {},
} ) => {
	// If the form step doesn't have a legend or title, render a <div> instead
	// of a <fieldset>.
	const Element = legend || title ? 'fieldset' : 'div';

	return (
		<Element
			className={ classnames(
				className,
				'wc-block-components-checkout-step',
				{
					'wc-block-components-checkout-step--with-step-number': showStepNumber,
					'wc-block-components-checkout-step--disabled': disabled,
				}
			) }
			id={ id }
			disabled={ disabled }
		>
			{ !! ( legend || title ) && (
				<legend className="screen-reader-text">
					{ legend || title }
				</legend>
			) }
			{ !! title && (
				<StepHeading
					title={ title }
					stepHeadingContent={ stepHeadingContent() }
				/>
			) }
			<div className="wc-block-components-checkout-step__container">
				{ !! description && (
					<p className="wc-block-components-checkout-step__description">
						{ description }
					</p>
				) }
				<div className="wc-block-components-checkout-step__content">
					{ children }
				</div>
			</div>
		</Element>
	);
};

FormStep.propTypes = {
	id: PropTypes.string,
	className: PropTypes.string,
	title: PropTypes.string,
	description: PropTypes.string,
	children: PropTypes.node,
	showStepNumber: PropTypes.bool,
	stepHeadingContent: PropTypes.func,
	disabled: PropTypes.bool,
	legend: PropTypes.string,
};

export default FormStep;
