/**
 * External dependencies
 */
import clsx from 'clsx';

/**
 * Internal dependencies
 */
import './style.scss';
import Title from '../title';

interface StepHeadingProps {
	title: string;
	stepHeadingContent?: JSX.Element;
}

const StepHeading = ( { title, stepHeadingContent }: StepHeadingProps ) => (
	<div className="wc-block-components-checkout-step__heading">
		<Title
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

export interface FormStepProps {
	id?: string;
	className?: string;
	title?: string;
	legend?: string;
	description?: string;
	children?: React.ReactNode;
	disabled?: boolean;
	showStepNumber?: boolean;
	stepHeadingContent?: () => JSX.Element | undefined;
}

const FormStep = ( {
	id,
	className,
	title,
	legend,
	description,
	children,
	disabled = false,
	showStepNumber = true,
	stepHeadingContent = () => undefined,
}: FormStepProps ): JSX.Element => {
	// If the form step doesn't have a legend or title, render a <div> instead
	// of a <fieldset>.
	const Element = legend || title ? 'fieldset' : 'div';

	return (
		<Element
			className={ clsx( className, 'wc-block-components-checkout-step', {
				'wc-block-components-checkout-step--with-step-number':
					showStepNumber,
				'wc-block-components-checkout-step--disabled': disabled,
			} ) }
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

export default FormStep;
