/**
 * External dependencies
 */
import { useSelect, dispatch } from '@wordpress/data';
import { useState } from 'react';

export const LYS_TOUR_HIDDEN = 'woocommerce_launch_your_store_tour_hidden';
export const useSiteVisibilityTour = () => {
	const [ showTour, setShowTour ] = useState( true );
	const { shouldTourBeShown } = useSelect( ( select ) => {
		const { getCurrentUser } = select( 'core' );
		const wasTourShown =
			( getCurrentUser() as { meta?: { [ key: string ]: string } } )
				?.meta?.[ LYS_TOUR_HIDDEN ] === 'yes';
		return {
			shouldTourBeShown: ! wasTourShown,
		};
	} );

	const onClose = () => {
		dispatch( 'core' ).saveUser( {
			id: window?.wcSettings?.currentUserId,
			meta: {
				[ LYS_TOUR_HIDDEN ]: 'yes',
			},
		} );
	};

	return {
		onClose,
		shouldTourBeShown,
		showTour,
		setShowTour,
	};
};
