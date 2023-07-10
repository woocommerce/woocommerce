/**
 * External dependencies
 */
import { withViewportMatch } from '@wordpress/viewport';
import { Card, CardBody, CardFooter, CardHeader } from '@wordpress/components';
import { createElement, useEffect, useRef } from '@wordpress/element';

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

	const stepRef = useRef< HTMLDivElement | undefined >();

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
		} else {
			// If no focus element is found, focus the last button in the step so that the user can navigate using keyboard.
			const buttons = stepRef.current?.querySelectorAll( 'button' );
			if ( buttons && buttons.length ) {
				setInitialFocusedElement( buttons[ buttons.length - 1 ] );
			}
		}
	}, [ focusElement, setInitialFocusedElement ] );

	return (
		<Card
			ref={ stepRef as React.LegacyRef< HTMLDivElement > }
			className="woocommerce-tour-kit-step"
			elevation={ 2 }
		>
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
