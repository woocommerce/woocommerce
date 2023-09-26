/**
 * External dependencies
 */
import Tour, { TourStepRendererProps } from '@automattic/tour-kit';
import { Button } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import React from 'react';

type TourSpotlightProps = {
	onDismiss: () => void;
	title: string;
	description: string;
	reference: string;
	className?: string;
};

export default function TourSpotlight( {
	onDismiss,
	title,
	description,
	reference,
	className,
}: TourSpotlightProps ) {
	const [ showTour, setShowTour ] = useState( true );

	const handleDismiss = () => {
		setShowTour( false );
		onDismiss();
	};
	// Define a configuration for the tour, passing along a handler for closing.
	const config = {
		steps: [
			{
				referenceElements: {
					desktop: reference,
				},
				meta: {
					description,
				},
			},
		],
		renderers: {
			tourStep: ( { currentStepIndex }: TourStepRendererProps ) => {
				return (
					<div className={ className }>
						<h3>{ title }</h3>
						<p>
							{
								config.steps[ currentStepIndex ].meta
									.description
							}
						</p>
						<Button onClick={ handleDismiss }>
							{ __( 'Got it', 'woocommerce' ) }
						</Button>
					</div>
				);
			},
			tourMinimized: () => <div />,
		},
		closeHandler: () => handleDismiss(),
		options: {},
	};

	if ( ! showTour ) {
		return null;
	}

	return <Tour config={ config } />;
}
