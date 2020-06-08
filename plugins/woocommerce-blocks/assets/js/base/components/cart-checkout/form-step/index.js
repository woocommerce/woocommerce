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
	<div className="wc-block-checkout-step__heading">
		<Title
			aria-hidden="true"
			className="wc-block-checkout-step__title"
			headingLevel="2"
		>
			{ title }
		</Title>
		{ !! stepHeadingContent && (
			<span className="wc-block-checkout-step__heading-content">
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
			<div className="wc-block-checkout-step__container">
				{ !! description && (
					<p className="wc-block-checkout-step__description">
						{ description }
					</p>
				) }
				<div className="wc-block-checkout-step__content">
					{ children }
				</div>
			</div>
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
