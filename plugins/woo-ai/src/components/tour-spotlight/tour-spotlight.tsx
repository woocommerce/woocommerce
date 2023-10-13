/**
 * External dependencies
 */
import { TourKit } from '@woocommerce/components';
import { store as preferencesStore } from '@wordpress/preferences';
import { useDispatch, select } from '@wordpress/data';
import { useState, useEffect } from '@wordpress/element';
import { Config } from '@automattic/tour-kit';

type TourSpotlightProps = {
	id: string;
	title: string;
	description: string;
	reference: string;
	placement?: Config[ 'placement' ];
	spotlightParent?: HTMLElement;
	onDismissal?: () => void;
	onStepViewOnce?: () => void;
};

export const TourSpotlight: React.FC< TourSpotlightProps > = ( {
	id,
	title,
	description,
	reference,
	placement = 'auto',
	spotlightParent = document.body,
	onDismissal = () => {},
	onStepViewOnce = () => {},
} ) => {
	const preferenceId = `spotlightDismissed-${ id }`;

	const anchorElement = document.querySelector( reference );
	const hasBeenDismissedBefore = select( preferencesStore ).get(
		'woo-ai-plugin',
		preferenceId
	);
	const { set } = useDispatch( preferencesStore );
	const [ isSpotlightVisible, setIsSpotlightVisible ] =
		useState< boolean >( false );

	// Avoids showing the spotlight before the layout is ready.
	useEffect( () => {
		const timeout = setTimeout( () => {
			setIsSpotlightVisible( true );
		}, 250 );

		return () => clearTimeout( timeout );
	}, [] );

	if ( ! anchorElement || hasBeenDismissedBefore || ! isSpotlightVisible ) {
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
							name: `product-feedback-tour-${ id }`,
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
						onStepViewOnce,
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
					set( 'woo-ai-plugin', preferenceId, true );
					onDismissal();
				},
			} }
		/>
	);
};
