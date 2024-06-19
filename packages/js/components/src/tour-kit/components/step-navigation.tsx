/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import type { WooTourStepRendererProps } from '../types';

type Props = Omit<
	WooTourStepRendererProps,
	'onMinimize' | 'setInitialFocusedElement'
>;

const StepNavigation: React.FunctionComponent< Props > = ( {
	currentStepIndex,
	onNextStep,
	onPreviousStep,
	onDismiss,
	steps,
} ) => {
	const isFirstStep = currentStepIndex === 0;
	const isLastStep = currentStepIndex === steps.length - 1;

	const { primaryButton = { text: '', isDisabled: false, isHidden: false } } =
		steps[ currentStepIndex ].meta;
	const { secondaryButton = { text: '' } } = steps[ currentStepIndex ].meta;
	const { skipButton = { text: '', isVisible: false } } =
		steps[ currentStepIndex ].meta;

	const SkipButton = (
		<Button
			className="woocommerce-tour-kit-step-navigation__skip-btn"
			variant="tertiary"
			onClick={ onDismiss( 'skip-btn' ) }
		>
			{ skipButton.text || __( 'Skip', 'woocommerce' ) }
		</Button>
	);

	const NextButton = (
		<Button
			className="woocommerce-tour-kit-step-navigation__next-btn"
			variant="primary"
			disabled={ primaryButton.isDisabled }
			onClick={ onNextStep }
		>
			{ primaryButton.text || __( 'Next', 'woocommerce' ) }
		</Button>
	);

	const BackButton = (
		<Button
			className="woocommerce-tour-kit-step-navigation__back-btn"
			variant="secondary"
			onClick={ onPreviousStep }
		>
			{ secondaryButton.text || __( 'Back', 'woocommerce' ) }
		</Button>
	);

	const renderButtons = () => {
		if ( isLastStep ) {
			return (
				<div>
					{ skipButton.isVisible ? SkipButton : null }
					{
						! isFirstStep ? BackButton : null // For 1 step tours, isFirstStep and isLastStep can be true simultaneously.
					}
					<Button
						variant="primary"
						disabled={ primaryButton.isDisabled }
						className="woocommerce-tour-kit-step-navigation__done-btn"
						onClick={ onDismiss( 'done-btn' ) }
					>
						{ primaryButton.text || __( 'Done', 'woocommerce' ) }
					</Button>
				</div>
			);
		}

		if ( isFirstStep ) {
			return (
				<div>
					{ skipButton.isVisible ? SkipButton : null }
					{ NextButton }
				</div>
			);
		}

		return (
			<div>
				{ skipButton.isVisible ? SkipButton : null }
				{ BackButton }
				{ NextButton }
			</div>
		);
	};

	if ( primaryButton.isHidden ) {
		return null;
	}

	return (
		<div className="woocommerce-tour-kit-step-navigation">
			<div className="woocommerce-tour-kit-step-navigation__step">
				{ steps.length > 1
					? sprintf(
							/* translators: current progress in tour, eg: "Step 2 of 4" */
							__( 'Step %1$d of %2$d', 'woocommerce' ),
							currentStepIndex + 1,
							steps.length
					  )
					: null }
			</div>
			{ renderButtons() }
		</div>
	);
};

export default StepNavigation;
