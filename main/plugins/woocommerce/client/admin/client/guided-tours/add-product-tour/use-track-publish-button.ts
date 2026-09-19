/**
 * External dependencies
 */
import { useEffect, useRef } from '@wordpress/element';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { bindPublishClickEvent } from '../utils';

export const useTrackPublishButton = ( showTour: boolean ) => {
	const unbindPublishClickEvent = useRef< () => void >( () => {} );
	useEffect( () => {
		if ( showTour ) {
			unbindPublishClickEvent.current = bindPublishClickEvent( () => {
				recordEvent( 'walkthrough_product_completed' );
			} );
		} else {
			unbindPublishClickEvent.current();
			unbindPublishClickEvent.current = () => {};
		}

		return function cleanup() {
			unbindPublishClickEvent.current();
		};
	}, [ showTour ] );
};
