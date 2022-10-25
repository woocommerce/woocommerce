/**
 * External dependencies
 */
import { useEffect, useState } from '@wordpress/element';
import { TourKit, TourKitTypes } from '@woocommerce/components';
import { useDispatch } from '@wordpress/data';
import { OPTIONS_STORE_NAME } from '@woocommerce/data';
import qs from 'qs';

/**
 * Internal dependencies
 */
import { waitUntilElementTopNotChange } from '../utils';
import { getTourConfig } from './get-config';

const WCAddonsTour = () => {
	const [ showTour, setShowTour ] = useState( false );
	const [ tourConfig, setTourConfig ] = useState< TourKitTypes.WooConfig >();

	const { updateOptions } = useDispatch( OPTIONS_STORE_NAME );

	const closeHandler: TourKitTypes.CloseHandler = (
		steps,
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
			// TODO: Track Tour as completed
		} else {
			// TODO: Track Tour as dismissed
		}
	};

	const onNextStepHandler = ( stepIndex: number ) => {
		// const stepName = tourConfig?.steps[ stepIndex ]?.meta?.name || '';
		// TODO: Maybe scroll to a proper element
		// TODO: Track tour's step as completed
	};

	const onPreviousStepHandler = ( stepIndex: number ) => {
		// TODO: Maybe scroll to a proper element
	};

	const defaultAutoScrollBlock: ScrollLogicalPosition = 'center';

	useEffect( () => {
		const theConfig = getTourConfig( {
			closeHandler,
			onNextStepHandler,
			onPreviousStepHandler,
			autoScrollBlock: defaultAutoScrollBlock,
		} );

		setTourConfig( theConfig );

		const query = qs.parse( window.location.search.slice( 1 ) );
		if ( query?.tutorial === 'true' ) {
			const intervalId = waitUntilElementTopNotChange(
				theConfig.steps[ 0 ].referenceElements?.desktop || '',
				() => {
					setShowTour( true );

					// TODO: track the tour is started
				},
				500
			);
			return () => clearInterval( intervalId );
		}
		// only run once
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [] );

	if ( ! showTour || ! tourConfig ) {
		return null;
	}

	return <TourKit config={ tourConfig } />;
};

export default WCAddonsTour;
