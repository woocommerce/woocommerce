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
	const anchorElement = document.querySelector( reference );
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
