/**
 * External dependencies
 */
import { TourKit } from '@woocommerce/components';
import { useState, useEffect } from '@wordpress/element';
import { Config } from '@automattic/tour-kit';

type TourSpotlightProps = {
	id: string;
	title: string | React.ReactElement;
	description: string;
	reference: string;
	placement?: Config[ 'placement' ];
	spotlightParent?: HTMLElement;
	onDismissal?: () => void;
	onDisplayed?: () => void;
};

export const TourSpotlight: React.FC< TourSpotlightProps > = ( {
	title,
	description,
	reference,
	placement = 'auto',
	spotlightParent = document.body,
	onDismissal = () => {},
	onDisplayed = () => {},
} ) => {
	// If the anchor element goes away, we want to hide the spotlight.
	const [ anchorElement, setAnchorElement ] = useState< HTMLElement | null >(
		document.querySelector( reference ) as HTMLElement
	);
	useEffect( () => {
		// Create a mutation observer to monitor the DOM for changes
		const observer = new MutationObserver( () => {
			// Update anchorElement state if the DOM or reference prop changes
			setAnchorElement(
				document.querySelector( reference ) as HTMLElement
			);
		} );

		// Start observing the document with the configured parameters
		observer.observe( document, { childList: true, subtree: true } );

		// Clean up the observer when the component is unmounted or when the reference prop changes
		return () => observer.disconnect();
	}, [ reference ] );

	const [ isSpotlightVisible, setIsSpotlightVisible ] =
		useState< boolean >( false );

	// Avoids showing the spotlight before the layout is ready.
	useEffect( () => {
		const timeout = setTimeout( () => {
			setIsSpotlightVisible( true );
		}, 250 );

		return () => clearTimeout( timeout );
	}, [] );

	if ( ! ( anchorElement && isSpotlightVisible ) ) {
		return null;
	}

	return (
		<TourKit
			config={ {
				steps: [
					{
						referenceElements: {
							desktop: reference,
						},
						meta: {
							name: `woo-ai-feature-spotlight`,
							heading: title,
							descriptions: {
								desktop: description,
							},
							primaryButton: {
								isHidden: true,
							},
						},
					},
				],
				placement,
				options: {
					callbacks: {
						onStepViewOnce: onDisplayed,
					},
					portalParentElement: spotlightParent,
					effects: {
						liveResize: {
							mutation: true,
							resize: true,
						},
					},
				},
				closeHandler: () => {
					setIsSpotlightVisible( false );
					onDismissal();
				},
			} }
		/>
	);
};
