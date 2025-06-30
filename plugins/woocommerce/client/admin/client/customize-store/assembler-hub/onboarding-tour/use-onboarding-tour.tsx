/**
 * External dependencies
 */
import { optionsStore } from '@woocommerce/data';
import { useSelect, useDispatch } from '@wordpress/data';
import { useState } from 'react';

export const CUSTOMIZE_STORE_ONBOARDING_TOUR_HIDDEN =
	'woocommerce_customize_store_onboarding_tour_hidden';

export const useOnboardingTour = () => {
	const [ showWelcomeTour, setShowWelcomeTour ] = useState( true );
	const [ isResizeHandleVisible, setIsResizeHandleVisible ] =
		useState( true );

	const { updateOptions } = useDispatch( optionsStore );
	const { shouldTourBeShown } = useSelect( ( select ) => {
		const { getOption, hasFinishedResolution } = select( optionsStore );

		const wasTourShown =
			getOption( CUSTOMIZE_STORE_ONBOARDING_TOUR_HIDDEN ) === 'yes' ||
			! hasFinishedResolution( 'getOption', [
				CUSTOMIZE_STORE_ONBOARDING_TOUR_HIDDEN,
			] );

		return {
			shouldTourBeShown: ! wasTourShown,
		};
	}, [] );

	const onClose = () => {
		updateOptions( {
			[ CUSTOMIZE_STORE_ONBOARDING_TOUR_HIDDEN ]: 'yes',
		} );
	};

	return {
		onClose,
		shouldTourBeShown,
		showWelcomeTour,
		setShowWelcomeTour,
		setIsResizeHandleVisible,
		isResizeHandleVisible,
	};
};
