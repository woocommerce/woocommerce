/**
 * External dependencies
 */
import { withViewportMatch } from '@wordpress/viewport';
import { Card, CardBody, CardFooter, CardHeader } from '@wordpress/components';
import { createElement, useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import StepNavigation from './step-navigation';
import StepControls from './step-controls';
import type { WooTourStepRendererProps } from '../types';

const getFocusElement = (
	focusElementSelector: string | null,
	iframeSelector: string | null
) => {
	if ( ! focusElementSelector ) {
		return null;
	}

	if ( iframeSelector ) {
		const iframeElement =
			document.querySelector< HTMLIFrameElement >( iframeSelector );
		if ( ! iframeElement ) {
			return null;
		}
		const innerDoc =
			iframeElement.contentDocument ||
			( iframeElement.contentWindow &&
				iframeElement.contentWindow.document );

		if ( ! innerDoc ) {
			return null;
		}

		return innerDoc.querySelector< HTMLElement >( focusElementSelector );
	}

	return document.querySelector< HTMLElement >( focusElementSelector );
};

const Step: React.FunctionComponent<
	WooTourStepRendererProps & {
		isViewportMobile: boolean;
	}
> = ( {
	steps,
	currentStepIndex,
	onDismiss,
	onNextStep,
	onPreviousStep,
	setInitialFocusedElement,
	onGoToStep,
	isViewportMobile,
} ) => {
	const { descriptions, heading } = steps[ currentStepIndex ].meta;
	const description =
		descriptions[ isViewportMobile ? 'mobile' : 'desktop' ] ??
		descriptions.desktop;

	const focusElementSelector =
		steps[ currentStepIndex ].focusElement?.[
			isViewportMobile ? 'mobile' : 'desktop'
		] || null;

	const iframeSelector =
		steps[ currentStepIndex ].focusElement?.iframe || null;

	const focusElement = getFocusElement(
		focusElementSelector,
		iframeSelector
	);

	/*
	 * Focus the element when step renders.
	 */
	useEffect( () => {
		if ( focusElement ) {
			setInitialFocusedElement( focusElement );
		}
	}, [ focusElement, setInitialFocusedElement ] );

	return (
		<Card className="woocommerce-tour-kit-step" isElevated>
			<CardHeader isBorderless={ true } size="small">
				<StepControls onDismiss={ onDismiss } />
			</CardHeader>
			<CardBody className="woocommerce-tour-kit-step__body" size="small">
				<h2 className="woocommerce-tour-kit-step__heading">
					{ heading }
				</h2>
				<p className="woocommerce-tour-kit-step__description">
					{ description }
				</p>
			</CardBody>
			<CardFooter isBorderless={ true } size="small">
				<StepNavigation
					currentStepIndex={ currentStepIndex }
					onGoToStep={ onGoToStep }
					onNextStep={ onNextStep }
					onPreviousStep={ onPreviousStep }
					onDismiss={ onDismiss }
					steps={ steps }
				></StepNavigation>
			</CardFooter>
		</Card>
	);
};

export default withViewportMatch( {
	isViewportMobile: '< medium',
} )( Step );
