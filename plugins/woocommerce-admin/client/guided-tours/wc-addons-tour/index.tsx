/**
 * External dependencies
 */
import { useEffect, useState } from '@wordpress/element';
import { TourKit, TourKitTypes } from '@woocommerce/components';
import { recordEvent } from '@woocommerce/tracks';
import { useDispatch } from '@wordpress/data';
import { OPTIONS_STORE_NAME } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { observePositionChange, waitUntilElementTopNotChange } from '../utils';
import { getTourConfig } from './get-config';
import { scrollPopperToVisibleAreaIfNeeded } from './utils';
import { getSteps } from './get-steps';

const WCAddonsTour = () => {
	const [ showTour, setShowTour ] = useState( true );

	const { updateOptions } = useDispatch( OPTIONS_STORE_NAME );

	const steps = getSteps();
	const defaultAutoScrollBlock: ScrollLogicalPosition = 'center';

	useEffect( () => {
		const query = new URLSearchParams( location.search );
		if ( query.get( 'tutorial' ) === 'true' ) {
			const intervalId = waitUntilElementTopNotChange(
				steps[ 0 ].referenceElements?.desktop || '',
				() => {
					const stepName = steps[ 0 ]?.meta?.name;
					setShowTour( true );
					recordEvent( 'in_app_marketplace_tour_started', {
						step: stepName,
					} );
				},
				500
			);
			return () => clearInterval( intervalId );
		}
		// only run once
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [] );

	useEffect( () => {
		if ( showTour ) {
			function showPopper() {
				const tourKitElement = document.querySelector(
					'.tour-kit-frame__container'
				);
				if ( tourKitElement ) {
					scrollPopperToVisibleAreaIfNeeded(
						tourKitElement.getBoundingClientRect()
					);
				}
			}

			// In a rare case, admin notices might load before observe is added below (moving `.wc-addons-wrap`).
			// In such a case, if Tour is shown before this effect is called, it might not be position correctly.
			// Updating popper's position here, ensures it's always visible.
			const timeoutId = setTimeout( showPopper, 500 );

			const intervalId = observePositionChange(
				'.woocommerce-marketplace',
				showPopper,
				150
			);
			return () => {
				clearTimeout( timeoutId );
				clearInterval( intervalId );
			};
		}
	}, [ showTour ] );

	if ( ! showTour ) {
		return null;
	}

	const closeHandler: TourKitTypes.CloseHandler = (
		tourSteps,
		currentStepIndex
	) => {
		setShowTour( false );
		// mark tour as completed
		updateOptions( {
			woocommerce_admin_dismissed_in_app_marketplace_tour: 'yes',
		} );
		// remove `tutorial` from search query, so it's not shown on page refresh
		const url = new URL( window.location.href );
		url.searchParams.delete( 'tutorial' );
		window.history.replaceState( null, '', url );

		if ( steps.length - 1 === currentStepIndex ) {
			recordEvent( 'in_app_marketplace_tour_completed' );
		} else {
			const stepName = tourSteps[ currentStepIndex ]?.meta?.name;
			recordEvent( 'in_app_marketplace_tour_dismissed', {
				step: stepName,
			} );
		}
	};

	const onNextStepHandler = ( newStepIndex: number ) => {
		const stepName = steps[ newStepIndex ]?.meta?.name || '';
		recordEvent( 'in_app_marketplace_tour_step_viewed', {
			step: stepName,
		} );
	};

	const tourConfig = getTourConfig( {
		closeHandler,
		onNextStepHandler,
		autoScrollBlock: defaultAutoScrollBlock,
		steps,
	} );

	return <TourKit config={ tourConfig } />;
};

export default WCAddonsTour;
