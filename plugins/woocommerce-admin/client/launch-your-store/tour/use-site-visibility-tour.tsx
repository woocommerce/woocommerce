/**
 * External dependencies
 */
import { OPTIONS_STORE_NAME } from '@woocommerce/data';
import { useSelect, useDispatch } from '@wordpress/data';
import { useState } from 'react';

export const LYS_TOUR_HIDDEN = 'woocommerce_launch_your_store_tour_hidden';

export const useSiteVisibilityTour = () => {
	const [ showTour, setShowTour ] = useState( true );
	const { updateOptions } = useDispatch( OPTIONS_STORE_NAME );
	const { shouldTourBeShown } = useSelect( ( select ) => {
		const { getOption, hasFinishedResolution } =
			select( OPTIONS_STORE_NAME );

		const wasTourShown =
			getOption( LYS_TOUR_HIDDEN ) === 'yes' ||
			! hasFinishedResolution( 'getOption', [ LYS_TOUR_HIDDEN ] );

		return {
			shouldTourBeShown: ! wasTourShown,
		};
	} );

	const onClose = () => {
		updateOptions( {
			[ LYS_TOUR_HIDDEN ]: 'yes',
		} );
	};

	return {
		onClose,
		shouldTourBeShown,
		showTour,
		setShowTour,
	};
};
