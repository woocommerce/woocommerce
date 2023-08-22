/**
 * External dependencies
 */
import { useDispatch, select } from '@wordpress/data';
import { store as preferencesStore } from '@wordpress/preferences';
import React from 'react';

/**
 * Internal dependencies
 */
import TourSpotlight from '../tour-spotlight/tour-spotlight';
import './info-modal.scss';

interface InfoModalProps {
	id: string;
	message: string;
	title: string;
}

export const InfoModal: React.FC< InfoModalProps > = ( {
	id,
	message,
	title,
} ) => {
	const anchorElement = document.querySelector( '#postexcerpt' );
	const hasBeenDismissedBefore = select( preferencesStore ).get(
		'woo-ai-plugin',
		`modalDismissed-${ id }`
	);

	const { set } = useDispatch( preferencesStore );

	if ( ! anchorElement || hasBeenDismissedBefore ) return null;

	const closeTour = () => {
		set( 'woo-ai-plugin', `modalDismissed-${ id }`, true );
	};

	return (
		<TourSpotlight
			title={ title }
			description={ message }
			onDismiss={ closeTour }
			reference={ '#postexcerpt' }
			className={ `${ id }-spotlight` }
		/>
	);
};
