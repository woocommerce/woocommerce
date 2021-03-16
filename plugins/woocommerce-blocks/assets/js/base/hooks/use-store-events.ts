/**
 * External dependencies
 */
import { doAction } from '@wordpress/hooks';
import { useCallback } from '@wordpress/element';

/**
 * Abstraction on top of @wordpress/hooks for dispatching events via doAction for 3rd parties to hook into.
 */
export const useStoreEvents = (
	namespace = 'experimental__woocommerce_blocks'
): {
	dispatchStoreEvent: (
		eventName: string,
		eventParams: Partial< Record< string, unknown > >
	) => void;
} => {
	const dispatchStoreEvent = useCallback(
		(
			eventName: string,
			eventParams: Partial< Record< string, unknown > >
		) => {
			// eslint-disable-next-line no-console
			console.log( {
				event: `${ namespace }-${ eventName }`,
				eventParams,
			} );

			try {
				doAction( `${ namespace }-${ eventName }`, eventParams );
			} catch ( e ) {
				// We don't handle thrown errors but just console.log for troubleshooting.
				// eslint-disable-next-line no-console
				console.error( e );
			}
		},
		[ namespace ]
	);

	return {
		dispatchStoreEvent,
	};
};
